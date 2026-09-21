<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordSetupLink;
use App\Models\BookingLocation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_800_users_and_password_setup_links_are_queued(): void
    {
        Queue::fake();

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
        $this->assertDatabaseCount('users', 801);
        Queue::assertPushed(SendPasswordSetupLink::class, 800);
        Queue::assertPushed(SendPasswordSetupLink::class, fn ($job) => $job->sendAccountCreatedMessage);
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
