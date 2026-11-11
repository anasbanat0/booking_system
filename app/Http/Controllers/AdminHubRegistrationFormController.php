<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\HubRegistrationField;
use App\Models\HubRegistrationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminHubRegistrationFormController extends Controller
{
    private const FIELD_TYPES = ['text', 'email', 'tel', 'textarea', 'radio', 'select', 'file'];

    private const CORE_FIELDS = ['full_name_ar', 'full_name_en', 'email'];

    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $forms = HubRegistrationForm::with(['location', 'fields'])
            ->whereHas('location', fn ($query) => $query->whereIn('slug', ['gaza', 'khan-younis']))
            ->get()
            ->sortBy(fn ($form) => $form->location?->slug === 'gaza' ? 0 : 1)
            ->values();

        return view('admin.hub-registration-forms.index', [
            'forms' => $forms,
            'fieldTypes' => self::FIELD_TYPES,
            'coreFields' => self::CORE_FIELDS,
        ]);
    }

    public function update(Request $request, BookingLocation $location)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $validated = $request->validate([
            'eyebrow' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'submit_button' => ['required', 'string', 'max:80'],
            'success_title' => ['required', 'string', 'max:180'],
            'success_message' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'integer'],
            'fields.*.label' => ['required', 'string', 'max:180'],
            'fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'fields.*.placeholder' => ['nullable', 'string', 'max:180'],
            'fields.*.help_text' => ['nullable', 'string', 'max:500'],
            'fields.*.options' => ['nullable', 'string', 'max:3000'],
            'fields.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_active' => ['nullable', 'boolean'],
            'new_field.key' => ['nullable', 'alpha_dash', 'max:80'],
            'new_field.label' => ['nullable', 'required_with:new_field.key', 'string', 'max:180'],
            'new_field.type' => ['nullable', 'required_with:new_field.key', Rule::in(self::FIELD_TYPES)],
            'new_field.placeholder' => ['nullable', 'string', 'max:180'],
            'new_field.help_text' => ['nullable', 'string', 'max:500'],
            'new_field.options' => ['nullable', 'string', 'max:3000'],
            'new_field.is_required' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $form, $validated) {
            $form->update([
                'eyebrow' => $validated['eyebrow'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'submit_button' => $validated['submit_button'],
                'success_title' => $validated['success_title'],
                'success_message' => $validated['success_message'],
                'is_active' => $request->boolean('is_active'),
            ]);

            foreach ($validated['fields'] as $index => $input) {
                $field = $form->fields->firstWhere('id', (int) $input['id']);
                abort_unless($field, 422);
                $isCore = in_array($field->key, self::CORE_FIELDS, true);

                $field->update([
                    'label' => $input['label'],
                    'type' => $isCore ? $field->type : $input['type'],
                    'placeholder' => $input['placeholder'] ?? null,
                    'help_text' => $input['help_text'] ?? null,
                    'options' => $this->options($input['options'] ?? ''),
                    'sort_order' => $input['sort_order'],
                    'is_required' => $isCore || $request->boolean('fields.'.$index.'.is_required'),
                    'is_active' => $isCore || $request->boolean('fields.'.$index.'.is_active'),
                ]);
            }

            if (! empty($validated['new_field']['key'])) {
                $key = Str::snake($validated['new_field']['key']);

                abort_if($form->fields()->where('key', $key)->exists(), 422, 'A field with this key already exists.');

                HubRegistrationField::create([
                    'hub_registration_form_id' => $form->id,
                    'key' => $key,
                    'label' => $validated['new_field']['label'],
                    'type' => $validated['new_field']['type'],
                    'placeholder' => $validated['new_field']['placeholder'] ?? null,
                    'help_text' => $validated['new_field']['help_text'] ?? null,
                    'options' => $this->options($validated['new_field']['options'] ?? ''),
                    'sort_order' => ((int) $form->fields()->max('sort_order')) + 10,
                    'is_required' => $request->boolean('new_field.is_required'),
                    'is_active' => true,
                ]);
            }
        });

        return back()->with('success', $location->name.' registration form updated successfully.');
    }

    private function options(string $value): ?array
    {
        $options = collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $options === [] ? null : $options;
    }
}
