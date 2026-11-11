<?php

namespace Tests\Feature;

use App\Models\BookingLocation;
use App\Models\HubRegistration;
use App\Models\HubRegistrationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HubRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_public_form_uses_its_branch_specific_fields(): void
    {
        $gaza = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $khanYounis = BookingLocation::where('slug', 'khan-younis')->firstOrFail();

        $this->get(route('hub-registration.show', $gaza))
            ->assertOk()
            ->assertSee('Gaza Hub Registration')
            ->assertSee('Gaza City')
            ->assertSee('North Gaza')
            ->assertDontSee('Middle Governorates');

        $this->get(route('hub-registration.show', $khanYounis))
            ->assertOk()
            ->assertSee('South Gaza Hub Registration')
            ->assertSee('Khan Younis')
            ->assertSee('Middle Governorates')
            ->assertDontSee('North Gaza');
    }

    public function test_applicant_can_submit_form_with_private_document(): void
    {
        Storage::fake('local');
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $payload = $this->payloadFor($form);

        $this->post(route('hub-registration.store', $location), $payload)
            ->assertRedirect(route('hub-registration.show', $location))
            ->assertSessionHas('registration_success');

        $registration = HubRegistration::with('files')->firstOrFail();
        $this->assertSame('Applicant Name', $registration->full_name_en);
        $this->assertSame('applicant@example.com', $registration->email);
        $this->assertCount(1, $registration->files);
        Storage::disk('local')->assertExists($registration->files->first()->path);

        $admin = User::factory()->create(['role' => 'admin']);
        $download = $this->actingAs($admin)->get(route('admin.hub-registrations.files.download', [
            $location,
            $registration->files->first(),
        ]));

        $download->assertOk()->assertDownload('university-id.jpg');
        $this->assertNotSame('', $download->streamedContent());
    }

    public function test_email_must_be_unique_inside_the_same_hub(): void
    {
        Storage::fake('local');
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();

        $this->post(route('hub-registration.store', $location), $this->payloadFor($form))->assertSessionHasNoErrors();
        $this->post(route('hub-registration.store', $location), $this->payloadFor($form))
            ->assertSessionHasErrors('answers.'.$form->fields->firstWhere('key', 'email')->id);
    }

    public function test_admin_can_filter_view_and_export_branch_registrations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $registration = HubRegistration::create([
            'booking_location_id' => $location->id,
            'full_name_ar' => 'اسم طالب',
            'full_name_en' => 'Test Applicant',
            'email' => 'test@example.com',
            'answers' => [],
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.hub-registrations.index', [$location, 'search' => 'Test Applicant']))
            ->assertOk()
            ->assertSee('Test Applicant');

        $this->actingAs($admin)->get(route('admin.hub-registrations.show', [$location, $registration]))
            ->assertOk()
            ->assertSee('Test Applicant');

        $this->actingAs($admin)->get(route('admin.hub-registration-forms.index'))
            ->assertOk()
            ->assertSee('Registration Forms');

        $response = $this->actingAs($admin)->get(route('admin.hub-registrations.export', $location));
        $response->assertOk();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_staff_can_only_access_assigned_branch_registrations(): void
    {
        $gaza = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $khanYounis = BookingLocation::where('slug', 'khan-younis')->firstOrFail();
        $staff = User::factory()->create(['role' => 'staff', 'booking_location_id' => $gaza->id]);

        $this->actingAs($staff)->get(route('admin.hub-registrations.index', $gaza))->assertOk();
        $this->actingAs($staff)->get(route('admin.hub-registrations.index', $khanYounis))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.hub-registration-forms.index'))->assertForbidden();
    }

    public function test_admin_can_edit_form_content_and_field_label(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $fields = $form->fields->map(fn ($field) => [
            'id' => $field->id,
            'label' => $field->key === 'gender' ? 'Applicant gender' : $field->label,
            'type' => $field->type,
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'options' => implode("\n", $field->options ?? []),
            'sort_order' => $field->sort_order,
            'is_required' => $field->is_required ? '1' : null,
            'is_active' => $field->is_active ? '1' : null,
        ])->all();

        $this->actingAs($admin)->patch(route('admin.hub-registration-forms.update', $location), [
            'eyebrow' => $form->eyebrow,
            'title' => 'Updated Gaza Application',
            'description' => $form->description,
            'instructions' => $form->instructions,
            'submit_button' => $form->submit_button,
            'success_title' => $form->success_title,
            'success_message' => $form->success_message,
            'is_active' => '1',
            'fields' => $fields,
        ])->assertRedirect();

        $this->get(route('hub-registration.show', $location))
            ->assertOk()
            ->assertSee('Updated Gaza Application')
            ->assertSee('Applicant gender');
    }

    private function payloadFor(HubRegistrationForm $form): array
    {
        $answers = [];
        $files = [];

        foreach ($form->fields as $field) {
            if ($field->type === 'file') {
                $files[$field->id] = UploadedFile::fake()->image('university-id.jpg');

                continue;
            }

            $answers[$field->id] = match ($field->key) {
                'full_name_ar' => 'اسم المتقدم',
                'full_name_en' => 'Applicant Name',
                'email' => 'applicant@example.com',
                'gender' => 'Male',
                'university' => 'Islamic University of Gaza',
                'academic_level' => 'Third Year',
                'university_id' => '2026001',
                'whatsapp_phone' => '+970599000001',
                'alternate_phone' => '+970599000002',
                'residence_status' => 'Resident',
                'housing_type' => 'Home',
                'governorate' => $form->location?->slug === 'gaza' ? 'Gaza City' : 'Khan Younis',
                'current_address' => 'Current address details',
                default => $field->options[0] ?? 'Test value',
            };
        }

        return compact('answers', 'files');
    }
}
