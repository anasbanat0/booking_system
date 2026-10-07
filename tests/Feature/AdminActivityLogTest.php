<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_activity_by_an_inclusive_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->createLog($admin, $student, 'Old activity', '2026-10-01 10:00:00');
        $this->createLog($admin, $student, 'From boundary', '2026-10-04 00:00:00');
        $this->createLog($admin, $student, 'To boundary', '2026-10-06 23:59:59');
        $this->createLog($admin, $student, 'Future activity', '2026-10-07 00:00:00');

        $response = $this->actingAs($admin)->get(route('admin.activity.index', [
            'from' => '2026-10-04',
            'to' => '2026-10-06',
        ]));

        $response->assertOk()
            ->assertSee('From boundary')
            ->assertSee('To boundary')
            ->assertDontSee('Old activity')
            ->assertDontSee('Future activity')
            ->assertSee(route('admin.activity.users.show', [
                'user' => $student,
                'from' => '2026-10-04',
                'to' => '2026-10-06',
            ]));
    }

    public function test_admin_can_open_one_student_history_and_filter_its_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $this->createLog($admin, $student, 'Selected student activity', '2026-10-05 12:00:00');
        $this->createLog($admin, $student, 'Selected student old activity', '2026-09-01 12:00:00');
        $this->createLog($admin, $otherStudent, 'Other student activity', '2026-10-05 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.activity.users.show', [
            'user' => $student,
            'from' => '2026-10-05',
            'to' => '2026-10-05',
        ]));

        $response->assertOk()
            ->assertSee($student->name)
            ->assertSee('Selected student activity')
            ->assertDontSee('Selected student old activity')
            ->assertDontSee('Other student activity')
            ->assertSee(route('admin.activity.users.show', [
                'user' => $admin,
                'from' => '2026-10-05',
                'to' => '2026-10-05',
            ]));
    }

    public function test_non_admin_cannot_view_student_activity_history(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($staff)
            ->get(route('admin.activity.users.show', $student))
            ->assertForbidden();
    }

    public function test_admin_can_open_an_admin_user_activity_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createLog($admin, $admin, 'Admin login activity', '2026-10-07 12:00:00');

        $this->actingAs($admin)
            ->get(route('admin.activity.users.show', $admin))
            ->assertOk()
            ->assertSee('Admin login activity')
            ->assertSee('Admin');
    }

    private function createLog(User $actor, User $student, string $title, string $createdAt): ActivityLog
    {
        $log = ActivityLog::create([
            'actor_id' => $actor->id,
            'user_id' => $student->id,
            'type' => 'test_activity',
            'title' => $title,
            'description' => $title,
        ]);

        $log->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $log;
    }
}
