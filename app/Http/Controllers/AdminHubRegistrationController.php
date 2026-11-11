<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\HubRegistration;
use App\Models\HubRegistrationFile;
use App\Models\HubRegistrationForm;
use App\Services\HubRegistrationExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return response()->streamDownload(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $file->original_name, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Length' => (string) $file->size,
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
            ->when($request->filled('to'), fn ($builder) => $builder->where('submitted_at', '<=', Carbon::parse($request->to)->endOfDay()));

        return $latestFirst ? $query->latest('submitted_at') : $query;
    }

    private function authorizeLocation(Request $request, BookingLocation $location): void
    {
        if ($request->user()->canManageAllBranches()) {
            return;
        }

        abort_unless((int) $request->user()->booking_location_id === (int) $location->id, 403);
    }
}
