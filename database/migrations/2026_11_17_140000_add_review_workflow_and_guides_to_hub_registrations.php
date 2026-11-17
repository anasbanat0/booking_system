<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('hub_registration_forms', 'instructions_file_disk', fn (Blueprint $table) => $table->string('instructions_file_disk')->nullable()->after('instructions'));
        $this->addColumnIfMissing('hub_registration_forms', 'instructions_file_path', fn (Blueprint $table) => $table->string('instructions_file_path')->nullable()->after('instructions_file_disk'));
        $this->addColumnIfMissing('hub_registration_forms', 'instructions_file_name', fn (Blueprint $table) => $table->string('instructions_file_name')->nullable()->after('instructions_file_path'));
        $this->addColumnIfMissing('hub_registration_forms', 'instructions_file_mime', fn (Blueprint $table) => $table->string('instructions_file_mime')->nullable()->after('instructions_file_name'));
        $this->addColumnIfMissing('hub_registration_forms', 'instructions_file_size', fn (Blueprint $table) => $table->unsignedBigInteger('instructions_file_size')->nullable()->after('instructions_file_mime'));

        $this->addColumnIfMissing('hub_registrations', 'status', fn (Blueprint $table) => $table->string('status', 20)->default('pending')->after('answers'));
        $this->addColumnIfMissing('hub_registrations', 'reviewed_by', fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by')->nullable()->after('status'));
        $this->addColumnIfMissing('hub_registrations', 'reviewed_at', fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable()->after('reviewed_by'));
        $this->addColumnIfMissing('hub_registrations', 'accepted_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('accepted_user_id')->nullable()->after('reviewed_at'));
        $this->addColumnIfMissing('hub_registrations', 'rejection_reason', fn (Blueprint $table) => $table->text('rejection_reason')->nullable()->after('accepted_user_id'));

        $this->addIndexIfMissing('hub_registrations', 'hub_registrations_reviewed_by_index', ['reviewed_by']);
        $this->addIndexIfMissing('hub_registrations', 'hub_registrations_accepted_user_id_index', ['accepted_user_id']);
        $this->addIndexIfMissing('hub_registrations', 'hub_registrations_status_index', ['booking_location_id', 'status', 'submitted_at']);

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

        $foreignKeys = collect(Schema::getForeignKeys('hub_registrations'))->pluck('name');

        Schema::table('hub_registrations', function (Blueprint $table) use ($foreignKeys) {
            if ($foreignKeys->contains('hub_registrations_accepted_user_id_foreign')) {
                $table->dropForeign('hub_registrations_accepted_user_id_foreign');
            }

            if ($foreignKeys->contains('hub_registrations_reviewed_by_foreign')) {
                $table->dropForeign('hub_registrations_reviewed_by_foreign');
            }
        });

        foreach (['hub_registrations_status_index', 'hub_registrations_reviewed_by_index', 'hub_registrations_accepted_user_id_index'] as $index) {
            if (Schema::hasIndex('hub_registrations', $index)) {
                Schema::table('hub_registrations', fn (Blueprint $table) => $table->dropIndex($index));
            }
        }

        $registrationColumns = array_values(array_filter(
            ['status', 'reviewed_by', 'reviewed_at', 'accepted_user_id', 'rejection_reason'],
            fn (string $column) => Schema::hasColumn('hub_registrations', $column)
        ));

        if ($registrationColumns !== []) {
            Schema::table('hub_registrations', fn (Blueprint $table) => $table->dropColumn($registrationColumns));
        }

        $formColumns = array_values(array_filter(
            ['instructions_file_disk', 'instructions_file_path', 'instructions_file_name', 'instructions_file_mime', 'instructions_file_size'],
            fn (string $column) => Schema::hasColumn('hub_registration_forms', $column)
        ));

        if ($formColumns !== []) {
            Schema::table('hub_registration_forms', fn (Blueprint $table) => $table->dropColumn($formColumns));
        }
    }

    private function addColumnIfMissing(string $tableName, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, $definition);
        }
    }

    private function addIndexIfMissing(string $tableName, string $index, array $columns): void
    {
        if (! Schema::hasIndex($tableName, $index)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $index));
        }
    }
};
