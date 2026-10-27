<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\BookingRule;
use App\Models\Slot;
use App\Models\User;
use App\Services\SlotGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyBookingPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calendar_uses_seven_day_periods_and_a_short_final_period(): void
    {
        Carbon::setTestNow('2026-10-27 10:00:00');
        $student = $this->student();

        $this->actingAs($student)
            ->get(route('calendar.index', ['period' => '2026-10-22']))
            ->assertOk()
            ->assertViewHas('weekDays', fn (array $days) => count($days) === 7
                && $days[0]['date']->toDateString() === '2026-10-22'
                && $days[6]['date']->toDateString() === '2026-10-28')
            ->assertViewHas('nextPeriod', '2026-10-29');

        $this->actingAs($student)
            ->get(route('calendar.index', ['period' => '2026-10-29']))
            ->assertOk()
            ->assertViewHas('weekDays', fn (array $days) => count($days) === 3
                && $days[0]['date']->toDateString() === '2026-10-29'
                && $days[2]['date']->toDateString() === '2026-10-31')
            ->assertViewHas('previousPeriod', '2026-10-22')
            ->assertViewHas('nextPeriod', '2026-11-01');
    }

    public function test_a_student_can_book_after_sixteen_monthly_bookings_when_the_new_period_has_quota(): void
    {
        Carbon::setTestNow('2026-10-29 08:00:00');
        $student = $this->student();

        foreach ([1, 3, 4, 5, 8, 10, 11, 12, 15, 17, 18, 19, 22, 24, 25, 26] as $day) {
            $slot = $this->slot($student, "2026-10-{$day}");
            Booking::create(['user_id' => $student->id, 'slot_id' => $slot->id, 'status' => 'completed']);
        }

        $targetSlot = $this->slot($student, '2026-10-31');

        $this->actingAs($student)
            ->post(route('book.slot'), ['slot_id' => $targetSlot->id])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Booking confirmed.');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $student->id,
            'slot_id' => $targetSlot->id,
            'status' => 'booked',
        ]);
    }

    public function test_a_fifth_booking_in_the_same_seven_day_period_is_rejected(): void
    {
        Carbon::setTestNow('2026-10-22 08:00:00');
        $student = $this->student();

        foreach ([22, 24, 25, 26] as $day) {
            $slot = $this->slot($student, "2026-10-{$day}");
            Booking::create(['user_id' => $student->id, 'slot_id' => $slot->id, 'status' => 'booked']);
        }

        $targetSlot = $this->slot($student, '2026-10-27');

        $this->actingAs($student)
            ->post(route('book.slot'), ['slot_id' => $targetSlot->id])
            ->assertSessionHasErrors('limit');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $student->id,
            'slot_id' => $targetSlot->id,
        ]);
    }

    public function test_rescheduling_cannot_bypass_the_four_booking_period_limit(): void
    {
        Carbon::setTestNow('2026-10-22 08:00:00');
        $student = $this->student();

        foreach ([22, 24, 25, 26] as $day) {
            $slot = $this->slot($student, "2026-10-{$day}");
            Booking::create(['user_id' => $student->id, 'slot_id' => $slot->id, 'status' => 'booked']);
        }

        $currentSlot = $this->slot($student, '2026-10-23');
        $currentSlot->update(['booked_count' => 1]);
        $booking = Booking::create([
            'user_id' => $student->id,
            'slot_id' => $currentSlot->id,
            'status' => 'booked',
        ]);
        $targetSlot = $this->slot($student, '2026-10-27');

        $this->actingAs($student)
            ->post(route('bookings.reschedule', $booking), ['slot_id' => $targetSlot->id])
            ->assertSessionHasErrors('limit');

        $this->assertSame($currentSlot->id, $booking->refresh()->slot_id);
    }

    public function test_rescheduling_keeps_the_existing_advance_period_behavior(): void
    {
        Carbon::setTestNow('2026-10-22 08:00:00');
        $student = $this->student();
        $currentSlot = $this->slot($student, '2026-10-24');
        $currentSlot->update(['booked_count' => 1]);
        $booking = Booking::create([
            'user_id' => $student->id,
            'slot_id' => $currentSlot->id,
            'status' => 'booked',
        ]);
        $nextPeriodSlot = $this->slot($student, '2026-10-29');

        $this->actingAs($student)
            ->post(route('bookings.reschedule', $booking), ['slot_id' => $nextPeriodSlot->id])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Booking rescheduled successfully.');

        $this->assertSame($nextPeriodSlot->id, $booking->refresh()->slot_id);
    }

    public function test_my_bookings_shows_the_dynamic_october_capacity_instead_of_sixteen(): void
    {
        Carbon::setTestNow('2026-10-27 10:00:00');
        $student = $this->student();

        $this->actingAs($student)
            ->get(route('bookings.my'))
            ->assertOk()
            ->assertViewHas('remaining', fn (array $remaining) => $remaining['monthlyCapacity'] === 18
                && $remaining['monthlyPeriodCount'] === 5
                && $remaining['weeklyLimit'] === 4)
            ->assertSee('Bookings in October 2026')
            ->assertSee('Up to 18 across 5 weekly periods')
            ->assertDontSee('Monthly remaining');
    }

    public function test_slot_generation_targets_the_short_final_period(): void
    {
        $result = app(SlotGenerationService::class)
            ->generateUpcomingPeriod(Carbon::parse('2026-10-27 10:00:00'));

        $this->assertTrue($result['generated']);
        $this->assertSame('2026-10-29', $result['start_date']);
        $this->assertSame('2026-10-31', $result['end_date']);
    }

    public function test_admin_can_save_rules_without_a_monthly_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.booking-rules.update'), [
                'weekly_limit' => 4,
                'reschedule_cutoff_hours' => 12,
                'reminder_hours_before' => 24,
                'advance_booking_days' => 1,
                'enforce_one_booking_per_day' => 1,
                'enforce_unique_time_period' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(4, BookingRule::current()->weekly_limit);
    }

    private function student(): User
    {
        $location = BookingLocation::query()->firstOrFail();

        return User::factory()->create([
            'role' => 'student',
            'booking_location_id' => $location->id,
        ]);
    }

    private function slot(User $student, string $date): Slot
    {
        return Slot::create([
            'booking_location_id' => $student->booking_location_id,
            'date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'capacity' => 30,
            'booked_count' => 0,
            'is_active' => true,
        ]);
    }
}
