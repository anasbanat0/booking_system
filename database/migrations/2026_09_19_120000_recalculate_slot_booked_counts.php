<?php

use App\Models\Booking;
use App\Models\Slot;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Slot::query()->select('id')->chunkById(200, function ($slots) {
            $counts = Booking::query()
                ->selectRaw('slot_id, COUNT(*) as total')
                ->whereIn('slot_id', $slots->pluck('id'))
                ->whereIn('status', Slot::OCCUPYING_STATUSES)
                ->groupBy('slot_id')
                ->pluck('total', 'slot_id');

            foreach ($slots as $slot) {
                Slot::whereKey($slot->id)->update([
                    'booked_count' => (int) ($counts[$slot->id] ?? 0),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Corrected booking counts should not be reverted to inconsistent historical values.
    }
};
