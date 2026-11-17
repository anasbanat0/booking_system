<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\HubRegistration;
use App\Models\HubRegistrationFile;
use App\Models\HubRegistrationForm;
use App\Models\ActivityLog;
use App\Services\HubRegistrationApprovalService;
use App\Services\HubRegistrationExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class AdminHubRegistrationController extends Controller
{
    public function index(Request $request, BookingLocation $location)
    {
        $this->authorizeLocation($request, $location);
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $query = $this->filteredQuery($request, $location);
        $registrations = $query->paginate(25)->withQueryString();

        return view('admin.hub-registrations.index', compact('location', 'form', 'registrations'));
    }

    public function show(Request $request, BookingLocation $location, HubRegistration $registration)
    {
        $this->authorizeLocation($request, $location);
        abort_unless((int) $registration->booking_location_id === (int) $location->id, 404);

        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $registration->load('files');

        return view('admin.hub-registrations.show', compact('location', 'form', 'registration'));
    }

    public function export(Request $request, BookingLocation $location, HubRegistrationExcelExporter $exporter)
    {
        $this->authorizeLocation($request, $location);
        $fields = HubRegistrationForm::where('booking_location_id', $location->id)
            ->firstOrFail()
            ->fields()
            ->get();

        return $exporter->download($location, $fields, $this->filteredQuery($request, $location, false));
    }

    public function downloadFile(Request $request, BookingLocation $location, int $registrationFileId)
    {
        $this->authorizeLocation($request, $location);
        $file = HubRegistrationFile::query()
            ->whereKey($registrationFileId)
            ->whereHas('registration', fn ($query) => $query->where('booking_location_id', $location->id))
            ->firstOrFail();
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        $stream = $disk->readStream($file->path);
        abort_unless(is_resource($stream), 404);

        $disposition = HeaderUtils::makeDisposition(
            $request->boolean('download') ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            $file->original_name,
            str_replace('%', '', Str::ascii($file->original_name)) ?: 'document'
        );

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Length' => (string) $file->size,
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function accept(Request $request, BookingLocation $location, HubRegistration $registration, HubRegistrationApprovalService $approvalService)
    {
        $this->authorizeRegistration($request, $location, $registration);

        try {
            $accepted = $approvalService->approve($registration, $request->user());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $accepted
            ? $registration->full_name_en.' was accepted and the welcome email was queued.'
            : $registration->full_name_en.' was already accepted.');
    }

    public function acceptSelected(Request $request, BookingLocation $location, HubRegistrationApprovalService $approvalService)
    {
        $this->authorizeLocation($request, $location);
        $validated = $request->validate([
            'registration_ids' => ['required', 'array', 'min:1', 'max:500'],
            'registration_ids.*' => ['integer'],
        ]);
        $registrations = HubRegistration::where('booking_location_id', $location->id)
            ->whereIn('id', $validated['registration_ids'])
            ->orderBy('id')
            ->get();
        $accepted = 0;
        $failed = [];

        foreach ($registrations as $registration) {
            try {
                if ($approvalService->approve($registration, $request->user(), $accepted)) {
                    $accepted++;
                }
            } catch (\Throwable $exception) {
                $failed[] = $registration->full_name_en.': '.$exception->getMessage();
            }
        }

        $response = back()->with('success', $accepted.' application(s) accepted and queued for welcome email delivery.');

        return $failed === [] ? $response : $response->with('error', implode(' ', $failed));
    }

    public function reject(Request $request, BookingLocation $location, HubRegistration $registration)
    {
        $this->authorizeRegistration($request, $location, $registration);

        if ($registration->status === 'accepted') {
            return back()->with('error', 'An accepted application cannot be rejected because its user account is already active.');
        }

        $registration->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'accepted_user_id' => null,
            'rejection_reason' => null,
        ]);

        ActivityLog::record('hub_registration_rejected', 'Hub registration rejected', $registration->full_name_en.' was rejected from '.$location->name.' Hub.', [
            'actor_id' => $request->user()->id,
            'properties' => ['registration_id' => $registration->id, 'location_id' => $location->id],
        ]);

        return back()->with('success', $registration->full_name_en.' was marked as rejected.');
    }

    private function filteredQuery(Request $request, BookingLocation $location, bool $latestFirst = true)
    {
        $query = HubRegistration::query()
            ->where('booking_location_id', $location->id)
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->search);
                $builder->where(function ($query) use ($search) {
                    $query->where('full_name_ar', 'like', '%'.$search.'%')
                        ->orWhere('full_name_en', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('from'), fn ($builder) => $builder->where('submitted_at', '>=', Carbon::parse($request->from)->startOfDay()))
            ->when($request->filled('to'), fn ($builder) => $builder->where('submitted_at', '<=', Carbon::parse($request->to)->endOfDay()))
            ->when(in_array($request->status, ['pending', 'accepted', 'rejected'], true), fn ($builder) => $builder->where('status', $request->status));

        return $latestFirst ? $query->latest('submitted_at') : $query;
    }

    private function authorizeLocation(Request $request, BookingLocation $location): void
    {
        if ($request->user()->canManageAllBranches()) {
            return;
        }

        abort_unless((int) $request->user()->booking_location_id === (int) $location->id, 403);
    }

    private function authorizeRegistration(Request $request, BookingLocation $location, HubRegistration $registration): void
    {
        $this->authorizeLocation($request, $location);
        abort_unless((int) $registration->booking_location_id === (int) $location->id, 404);
    }
}
