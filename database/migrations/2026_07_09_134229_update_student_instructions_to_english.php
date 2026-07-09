<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $instructions = <<<'HTML'
<h2>Operating days</h2>
<p>The Medical Hub is open from Saturday to Thursday.</p>

<h2>Daily time periods</h2>
<ul>
    <li>First period: 8:30 AM to 11:30 AM.</li>
    <li>Second period: 11:30 AM to 2:30 PM.</li>
    <li>Third period: 2:30 PM to 5:30 PM.</li>
</ul>

<h2>Booking rules</h2>
<ul>
    <li>Each student may book up to 12 times per month.</li>
    <li>Each student may book one period per day, with a maximum of 3 bookings per week.</li>
    <li>Bookings are weekly only. Monthly booking is not available.</li>
</ul>

<h2>Cancellation and rescheduling</h2>
<ul>
    <li>A booking may be cancelled at any time, but the student cannot replace it with another booking after cancellation.</li>
    <li>A booking may be rescheduled to another available time up to 12 hours before the original booking starts.</li>
    <li>For emergency cancellations, the student may request compensation by contacting the on-site administrator and explaining the reason for cancellation.</li>
    <li>If a student cancels or misses more than 3 bookings in one month, one available booking day will be deducted from the student.</li>
</ul>

<h2>Booking or cancellation support</h2>
<p>If you face an issue with booking or cancellation through the website, contact the following number by direct call or WhatsApp during official working hours only: <strong>00972597231717</strong>.</p>

<h2>Conduct inside the hub</h2>
<ul>
    <li>Please keep the space quiet.</li>
    <li>Eating inside the hub is not allowed.</li>
    <li>Please leave the space clean and organized after use out of respect for other students.</li>
</ul>

<h2>Time commitment</h2>
<p>Please arrive on time for your booking to make full use of the reserved period.</p>

<h2>Use of resources</h2>
<ul>
    <li>Hub resources are for study purposes only.</li>
    <li>Tools or equipment may not be moved outside the hub without permission.</li>
    <li>Only the device being used may be charged inside the hub, to help preserve electricity and allow all students to benefit.</li>
</ul>

<h2>Personal responsibility</h2>
<ul>
    <li>Students are responsible for any damage to furniture or equipment caused by misuse.</li>
    <li>Protecting the hub and its property is a shared responsibility that helps keep the service available at a high standard.</li>
</ul>

<h2>Booking priority</h2>
<p>Priority for same-day booking and use of the hub is given to students who have exams.</p>

<h2>Feedback and evaluation</h2>
<ul>
    <li>The hub administration periodically reviews student usage to improve the service.</li>
    <li>Please cooperate in completing any requested surveys.</li>
</ul>
HTML;

        foreach ([
            'instructions_en' => $instructions,
            'instructions_ar' => '',
            'usage_instructions' => '',
        ] as $key => $value) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_contents')->where('key', 'instructions_en')->update([
            'value' => '<p>Read the booking rules carefully before choosing a weekly slot.</p>',
            'updated_at' => now(),
        ]);
    }
};
