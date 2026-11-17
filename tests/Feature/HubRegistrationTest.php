<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordSetupLink;
use App\Models\BookingLocation;
use App\Models\HubRegistration;
use App\Models\HubRegistrationForm;
use App\Models\User;
use App\Notifications\HubAcceptancePasswordSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use ZipArchive;

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
            ->assertSee('Do you have a reliable internet connection')
            ->assertSee('Do you have a regular electricity supply')
            ->assertSee('Why would you like to use the Medical Hub?')
            ->assertSee('Are you able to attend regularly')
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

        $download->assertOk();
        $this->assertStringContainsString('inline', (string) $download->headers->get('content-disposition'));
        $this->assertNotSame('', $download->streamedContent());

        $attachment = $this->actingAs($admin)->get(route('admin.hub-registrations.files.download', [
            $location,
            $registration->files->first(),
            'download' => 1,
        ]));
        $attachment->assertOk()->assertDownload('university-id.jpg');

        $export = $this->actingAs($admin)->get(route('admin.hub-registrations.export', $location));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($export->getFile()->getPathname()) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $relationshipsXml = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        $zip->close();
        $this->assertStringContainsString('<hyperlinks>', $sheetXml);
        $this->assertStringContainsString('ref="K2"', $sheetXml);
        $this->assertStringContainsString('/admin/registrations/gaza/files/', $relationshipsXml);
        $this->assertStringContainsString('TargetMode="External"', $relationshipsXml);
        $this->assertStringContainsString('Open university-id.jpg', $sheetXml);
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
            ->assertSee('Test Applicant')
            ->assertSee('Accept selected applications?')
            ->assertDontSee("return confirm(", false);

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
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $descriptionInput = '<p onclick="alert(1)">Medical Hub study spaces.</p><p><strong class="location">📍Location:</strong> Gaza City, Lego Space Building, 4th Floor.</p><ol><li>Bring your university ID.</li><li>Use active contact details.</li></ol><script>alert(1)</script>';
        $description = '<p>Medical Hub study spaces.</p><p><strong>📍Location:</strong> Gaza City, Lego Space Building, 4th Floor.</p><ol><li>Bring your university ID.</li><li>Use active contact details.</li></ol>';
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
            'description' => $descriptionInput,
            'instructions' => $form->instructions,
            'submit_button' => $form->submit_button,
            'success_title' => $form->success_title,
            'success_message' => $form->success_message,
            'is_active' => '1',
            'fields' => $fields,
            'instructions_pdf' => UploadedFile::fake()->createWithContent('gaza-guide.pdf', '%PDF-1.4 guide'),
        ])->assertRedirect(route('admin.hub-registration-forms.index', ['hub' => $location->slug]))
            ->assertSessionHasNoErrors();

        $this->assertSame($description, $form->fresh()->description);
        Storage::disk('local')->assertExists($form->fresh()->instructions_file_path);

        $this->get(route('hub-registration.show', $location))
            ->assertOk()
            ->assertSee('Updated Gaza Application')
            ->assertSee('<strong>📍Location:</strong>', false)
            ->assertSee('<ol><li>Bring your university ID.</li>', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('alert(1)')
            ->assertSee('Open PDF')
            ->assertSee('Applicant gender');

        $guide = $this->get(route('hub-registration.instructions-file', $location));
        $guide->assertOk();
        $this->assertStringContainsString('inline', (string) $guide->headers->get('content-disposition'));
        $this->assertSame('%PDF-1.4 guide', $guide->streamedContent());
    }

    public function test_admin_can_accept_and_reject_applications_for_the_correct_hub(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::where('slug', 'gaza')->firstOrFail();
        $form = HubRegistrationForm::with('fields')->where('booking_location_id', $location->id)->firstOrFail();
        $phoneField = $form->fields->firstWhere('key', 'whatsapp_phone');
        $accepted = HubRegistration::create([
            'booking_location_id' => $location->id,
            'full_name_ar' => 'Accepted Arabic Name',
            'full_name_en' => 'Accepted Student',
            'email' => 'accepted@example.com',
            'answers' => [(string) $phoneField->id => '+970599123456'],
            'submitted_at' => now(),
        ]);
        $rejected = HubRegistration::create([
            'booking_location_id' => $location->id,
            'full_name_ar' => 'Rejected Arabic Name',
            'full_name_en' => 'Rejected Student',
            'email' => 'rejected@example.com',
            'answers' => [],
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hub-registrations.accept', [$location, $accepted]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $student = User::where('email', 'accepted@example.com')->firstOrFail();
        $this->assertSame('student', $student->role);
        $this->assertSame($location->id, $student->booking_location_id);
        $this->assertSame('+970599123456', $student->phone);
        $this->assertDatabaseHas('hub_registrations', [
            'id' => $accepted->id,
            'status' => 'accepted',
            'accepted_user_id' => $student->id,
        ]);
        Queue::assertPushed(SendPasswordSetupLink::class, function ($job) use ($student) {
            return $job->userId === $student->id
                && $job->whatsAppGroupUrl === 'https://chat.whatsapp.com/K7DLZDOYdhmH6j6PeuWc88'
                && filled($job->hubName);
        });

        $this->actingAs($admin)->post(route('admin.hub-registrations.accept', [$location, $accepted]))->assertSessionHas('success');
        Queue::assertPushed(SendPasswordSetupLink::class, 1);

        $this->actingAs($admin)->post(route('admin.hub-registrations.reject', [$location, $rejected]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('hub_registrations', ['id' => $rejected->id, 'status' => 'rejected']);

        $this->actingAs($admin)->get(route('admin.hub-registrations.index', [$location, 'status' => 'accepted']))
            ->assertOk()
            ->assertSee('Accepted Student')
            ->assertSee('1 results');
    }

    public function test_admin_can_accept_multiple_selected_applications(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::where('slug', 'khan-younis')->firstOrFail();
        $registrations = collect(['one', 'two'])->map(fn ($name) => HubRegistration::create([
            'booking_location_id' => $location->id,
            'full_name_ar' => $name,
            'full_name_en' => ucfirst($name).' Student',
            'email' => $name.'@example.com',
            'answers' => [],
            'submitted_at' => now(),
        ]));

        $this->actingAs($admin)->post(route('admin.hub-registrations.accept-selected', $location), [
            'registration_ids' => $registrations->pluck('id')->all(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(2, HubRegistration::where('status', 'accepted')->count());
        Queue::assertPushed(SendPasswordSetupLink::class, 2);
        Queue::assertPushed(SendPasswordSetupLink::class, fn ($job) => $job->whatsAppGroupUrl === 'https://chat.whatsapp.com/BgGLJZgGl2hKvVYM6ecslC');
    }

    public function test_acceptance_password_email_contains_the_hub_group_but_regular_reset_does_not(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'student']);
        $groupUrl = 'https://chat.whatsapp.com/K7DLZDOYdhmH6j6PeuWc88';

        (new SendPasswordSetupLink($user->id, true, $groupUrl, 'Gaza'))->handle();

        Notification::assertSentTo($user, HubAcceptancePasswordSetup::class, fn ($notification) =>
            $notification->whatsAppGroupUrl === $groupUrl && $notification->hubName === 'Gaza'
        );

        $mailHtml = (string) (new HubAcceptancePasswordSetup('test-token', $groupUrl, 'Gaza'))
            ->toMail($user)
            ->render();
        $this->assertStringContainsString('Set your password', $mailHtml);
        $this->assertStringContainsString('Join WhatsApp group', $mailHtml);
        $this->assertStringContainsString($groupUrl, $mailHtml);
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
