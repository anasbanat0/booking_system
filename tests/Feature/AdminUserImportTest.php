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
}
