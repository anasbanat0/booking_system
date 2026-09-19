<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_cancellation_releases_a_slot_seat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = BookingLocation::query()->firstOrFail();
        $student = User::factory()->create([
            'role' => 'student',
            'booking_location_id' => $location->id,
        ]);
        $slot = Slot::create([
            'booking_location_id' => $location->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'capacity' => 35,
            'booked_count' => 1,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $student->id,
            'slot_id' => $slot->id,
            'status' => 'booked',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.status', $booking), ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertSame(1, $slot->refresh()->booked_count);

        $this->actingAs($admin)
            ->post(route('admin.bookings.status', $booking), ['status' => 'no_show'])
            ->assertOk()
            ->assertJsonPath('status', 'no_show');

        $this->assertSame(1, $slot->refresh()->booked_count);

        $this->actingAs($admin)
            ->post(route('admin.bookings.status', $booking), ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertSame(0, $slot->refresh()->booked_count);
    }
}
