<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\HubRegistration;
use App\Models\HubRegistrationFile;
use App\Models\HubRegistrationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HubRegistrationController extends Controller
{
    public function show(BookingLocation $location)
    {
        abort_unless($location->is_active, 404);

        $form = $this->formFor($location);

        return view('hub-registration.show', [
            'location' => $location,
            'form' => $form,
            'fields' => $form->fields()->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request, BookingLocation $location)
    {
        abort_unless($location->is_active, 404);

        $form = $this->formFor($location);
        $fields = $form->fields()->where('is_active', true)->get();
        $rules = [];

        foreach ($fields as $field) {
            $key = $field->type === 'file' ? 'files.'.$field->id : 'answers.'.$field->id;
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $fieldRules = match ($field->type) {
                'email' => [...$fieldRules, 'email:rfc', 'max:255'],
                'tel' => [...$fieldRules, 'string', 'max:50'],
                'textarea' => [...$fieldRules, 'string', 'max:5000'],
                'radio', 'select' => [...$fieldRules, 'string', Rule::in($field->options ?? [])],
                'file' => [...$fieldRules, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
                default => [...$fieldRules, 'string', 'max:500'],
            };

            if ($field->key === 'email') {
                $fieldRules[] = Rule::unique('hub_registrations', 'email')
                    ->where(fn ($query) => $query->where('booking_location_id', $location->id));
            }

            $rules[$key] = $fieldRules;
        }

        $validated = $request->validate($rules, [
            'answers.*.required' => 'This field is required.',
            'files.*.required' => 'Please upload the required university ID document.',
        ]);
        $answers = [];
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $location, $fields, $validated, &$answers, &$storedPaths) {
                foreach ($fields->where('type', '!=', 'file') as $field) {
                    $answers[(string) $field->id] = $validated['answers'][$field->id] ?? null;
                }

                $registration = HubRegistration::create([
                    'booking_location_id' => $location->id,
                    'full_name_ar' => $this->answerByKey($fields, $answers, 'full_name_ar'),
                    'full_name_en' => $this->answerByKey($fields, $answers, 'full_name_en'),
                    'email' => mb_strtolower($this->answerByKey($fields, $answers, 'email')),
                    'answers' => $answers,
                    'submitted_at' => now(),
                ]);

                foreach ($fields->where('type', 'file') as $field) {
                    $file = $request->file('files.'.$field->id);

                    if (! $file) {
                        continue;
                    }

                    $path = $file->storeAs(
                        'hub-registrations/'.$location->slug.'/'.$registration->id,
                        Str::uuid().'.'.$file->getClientOriginalExtension(),
                        'local'
                    );
                    $storedPaths[] = $path;

                    HubRegistrationFile::create([
                        'hub_registration_id' => $registration->id,
                        'hub_registration_field_id' => $field->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()
            ->route('hub-registration.show', $location)
            ->with('registration_success', [
                'title' => $form->success_title,
                'message' => $form->success_message,
            ]);
    }

    private function formFor(BookingLocation $location): HubRegistrationForm
    {
        return HubRegistrationForm::query()
            ->where('booking_location_id', $location->id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function answerByKey($fields, array $answers, string $key): string
    {
        $field = $fields->firstWhere('key', $key);

        return trim((string) ($field ? ($answers[(string) $field->id] ?? '') : ''));
    }
}
