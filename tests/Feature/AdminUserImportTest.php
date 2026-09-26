<?php

namespace Tests\Feature;

use App\Jobs\ImportUsersCsvChunk;
use App\Jobs\PrepareUsersCsvImport;
use App\Jobs\SendPasswordSetupLink;
use App\Models\BookingLocation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_queue_an_800_user_csv_without_processing_it_in_the_web_request(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::query()->firstOrFail();
        $rows = ['name,email,phone,role,branch,password'];

        for ($index = 1; $index <= 800; $index++) {
            $rows[] = "Student {$index},student{$index}@example.com,,student,{$location->name},";
        }

        $response = $this->actingAs($admin)->post(route('admin.manage.users.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', implode("\n", $rows)),
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('users', 1);
        Queue::assertPushed(PrepareUsersCsvImport::class, 1);
        Queue::assertNotPushed(ImportUsersCsvChunk::class);
        Queue::assertNotPushed(SendPasswordSetupLink::class);
    }

    public function test_queued_csv_file_is_split_into_small_background_chunks(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::query()->firstOrFail();
        $rows = ['name,email,phone,role,branch,password'];

        for ($index = 1; $index <= 800; $index++) {
            $rows[] = "Student {$index},student{$index}@example.com,,student,{$location->name},";
        }

        Storage::disk('local')->put('imports/students.csv', implode("\n", $rows));

        (new PrepareUsersCsvImport(
            'imports/students.csv',
            [strtolower($location->name) => $location->id],
            $admin->id,
            true,
            null,
        ))->handle();

        Queue::assertPushed(ImportUsersCsvChunk::class, 32);
        Storage::disk('local')->assertMissing('imports/students.csv');
    }

    public function test_queued_csv_file_is_kept_when_preparation_fails_so_it_can_be_retried(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('local')->put('imports/invalid.csv', "wrong,columns\nvalue,value");

        try {
            (new PrepareUsersCsvImport(
                'imports/invalid.csv',
                [],
                $admin->id,
                true,
                null,
            ))->handle();

            $this->fail('Expected the invalid CSV to be rejected.');
        } catch (ValidationException) {
            Storage::disk('local')->assertExists('imports/invalid.csv');
        }
    }

    public function test_queued_csv_accepts_cp1256_text_when_mbstring_does_not_support_windows_1256(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::query()->firstOrFail();
        $arabicName = iconv('UTF-8', 'CP1256', 'احمد');
        Storage::disk('local')->put(
            'imports/cp1256.csv',
            "name,email,phone,role,branch,password\n{$arabicName},cp1256@example.com,,student,{$location->name},",
        );

        (new PrepareUsersCsvImport(
            'imports/cp1256.csv',
            [strtolower($location->name) => $location->id],
            $admin->id,
            true,
            null,
        ))->handle();

        Queue::assertPushed(ImportUsersCsvChunk::class, function ($job) {
            return $job->rows[0]['name'] === 'احمد';
        });
        Storage::disk('local')->assertMissing('imports/cp1256.csv');
    }

    public function test_csv_chunk_imports_users_and_queues_their_password_setup_links(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::query()->firstOrFail();
        $locations = [strtolower($location->name) => $location->id];
        $rows = [
            [
                'name' => 'First Student',
                'email' => 'first.student@example.com',
                'phone' => '+972500000010',
                'role' => 'student',
                'branch' => $location->name,
                'password' => '',
            ],
            [
                'name' => 'Second Student',
                'email' => 'second.student@example.com',
                'phone' => '+972500000011',
                'role' => 'student',
                'branch' => $location->name,
                'password' => '',
            ],
        ];

        (new ImportUsersCsvChunk($rows, $locations, $admin->id, true, null))->handle();

        $this->assertDatabaseHas('users', ['email' => 'first.student@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'second.student@example.com']);
        Queue::assertPushed(SendPasswordSetupLink::class, 2);
        Queue::assertPushed(SendPasswordSetupLink::class, fn ($job) => $job->sendAccountCreatedMessage);
    }

    public function test_csv_with_duplicate_headers_returns_a_visible_validation_error(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('local')->put(
            'imports/duplicate.csv',
            "name,email,email\nStudent,student@example.com,duplicate@example.com",
        );

        try {
            (new PrepareUsersCsvImport(
                'imports/duplicate.csv',
                [],
                $admin->id,
                true,
                null,
            ))->handle();

            $this->fail('Expected duplicate CSV headers to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        Queue::assertNotPushed(ImportUsersCsvChunk::class);
        Storage::disk('local')->assertExists('imports/duplicate.csv');
    }

    public function test_admin_can_queue_setup_links_for_existing_users(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'student']);

        $response = $this->actingAs($admin)->post(route('admin.manage.users.password-links'));

        $response->assertRedirect()->assertSessionHas('success');
        Queue::assertPushed(SendPasswordSetupLink::class, 4);
    }

    public function test_password_setup_job_sends_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        (new SendPasswordSetupLink($user->id))->handle();

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'type' => 'password_setup_link_accepted',
        ]);
    }

    public function test_manual_creation_restores_a_deleted_account_instead_of_crashing_on_unique_email(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $deletedUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'returning@example.com',
            'phone' => '+972500000001',
            'role' => 'student',
        ]);
        $deletedUser->delete();

        $response = $this->actingAs($admin)->post(route('admin.manage.users.store'), [
            'name' => 'Returning Admin',
            'email' => 'RETURNING@example.com',
            'phone' => '+972500000001',
            'role' => 'admin',
            'password' => '',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'id' => $deletedUser->id,
            'name' => 'Returning Admin',
            'email' => 'returning@example.com',
            'role' => 'admin',
            'deleted_at' => null,
        ]);
        Queue::assertPushed(SendPasswordSetupLink::class, fn ($job) => $job->userId === $deletedUser->id);
    }

    public function test_manual_creation_reports_when_email_and_phone_match_different_deleted_accounts(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $emailOwner = User::factory()->create([
            'email' => 'email-owner@example.com',
            'phone' => '+972500000002',
        ]);
        $phoneOwner = User::factory()->create([
            'email' => 'phone-owner@example.com',
            'phone' => '+972500000003',
        ]);
        $emailOwner->delete();
        $phoneOwner->delete();

        $response = $this->actingAs($admin)
            ->from(route('admin.manage.users.index'))
            ->post(route('admin.manage.users.store'), [
                'name' => 'Conflicting User',
                'email' => $emailOwner->email,
                'phone' => $phoneOwner->phone,
                'role' => 'admin',
            ]);

        $response->assertRedirect(route('admin.manage.users.index'))
            ->assertSessionHasErrors(['email', 'phone']);
        Queue::assertNothingPushed();
    }
}
