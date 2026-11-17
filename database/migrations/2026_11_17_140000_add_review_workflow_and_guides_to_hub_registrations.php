<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_registration_forms', function (Blueprint $table) {
            $table->string('instructions_file_disk')->nullable()->after('instructions');
            $table->string('instructions_file_path')->nullable()->after('instructions_file_disk');
            $table->string('instructions_file_name')->nullable()->after('instructions_file_path');
            $table->string('instructions_file_mime')->nullable()->after('instructions_file_name');
            $table->unsignedBigInteger('instructions_file_size')->nullable()->after('instructions_file_mime');
        });

        Schema::table('hub_registrations', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('answers');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('accepted_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('accepted_user_id');
            $table->index(['booking_location_id', 'status', 'submitted_at'], 'hub_registrations_status_index');
        });

        $fields = [
            ['internet_connection', 'Do you have a reliable internet connection at your place of residence?', 'radio', ['Yes', 'No']],
            ['electricity_supply', 'Do you have a regular electricity supply at your place of residence?', 'radio', ['Yes', 'No']],
            ['medical_hub_reason', 'Why would you like to use the Medical Hub?', 'textarea', null],
            ['regular_attendance', 'Are you able to attend regularly after registration?', 'radio', ['Yes', 'No']],
        ];

        foreach (DB::table('hub_registration_forms')->pluck('id') as $formId) {
            $sortOrder = (int) DB::table('hub_registration_fields')
                ->where('hub_registration_form_id', $formId)
                ->max('sort_order');

            foreach ($fields as [$key, $label, $type, $options]) {
                $sortOrder += 10;

                DB::table('hub_registration_fields')->updateOrInsert(
                    ['hub_registration_form_id' => $formId, 'key' => $key],
                    [
                        'label' => $label,
                        'type' => $type,
                        'placeholder' => null,
                        'help_text' => null,
                        'options' => $options ? json_encode($options) : null,
                        'is_required' => true,
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('hub_registration_fields')
            ->whereIn('key', ['internet_connection', 'electricity_supply', 'medical_hub_reason', 'regular_attendance'])
            ->delete();

        Schema::table('hub_registrations', function (Blueprint $table) {
            $table->dropIndex('hub_registrations_status_index');
            $table->dropConstrainedForeignId('accepted_user_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'reviewed_at', 'rejection_reason']);
        });

        Schema::table('hub_registration_forms', function (Blueprint $table) {
            $table->dropColumn([
                'instructions_file_disk',
                'instructions_file_path',
                'instructions_file_name',
                'instructions_file_mime',
                'instructions_file_size',
            ]);
        });
    }
};
