<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_registration_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_location_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('eyebrow')->default('Medical Hub Registration');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('submit_button')->default('Submit registration');
            $table->string('success_title')->default('Registration received');
            $table->text('success_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hub_registration_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_registration_form_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type', 30)->default('text');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hub_registration_form_id', 'key']);
        });

        Schema::create('hub_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_location_id')->constrained()->cascadeOnDelete();
            $table->string('full_name_ar');
            $table->string('full_name_en');
            $table->string('email');
            $table->json('answers');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['booking_location_id', 'email']);
            $table->index(['booking_location_id', 'submitted_at']);
        });

        Schema::create('hub_registration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hub_registration_field_id')->constrained()->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->unique(['hub_registration_id', 'hub_registration_field_id'], 'hub_registration_file_unique');
        });

        $this->seedForms();
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_registration_files');
        Schema::dropIfExists('hub_registrations');
        Schema::dropIfExists('hub_registration_fields');
        Schema::dropIfExists('hub_registration_forms');
    }

    private function seedForms(): void
    {
        $locations = DB::table('booking_locations')
            ->whereIn('slug', ['gaza', 'khan-younis'])
            ->get();

        foreach ($locations as $location) {
            $isGaza = $location->slug === 'gaza';
            $formId = DB::table('hub_registration_forms')->insertGetId([
                'booking_location_id' => $location->id,
                'eyebrow' => 'Samir Foundation Medical Hub',
                'title' => $isGaza ? 'Gaza Hub Registration' : 'South Gaza Hub Registration',
                'description' => 'Samir Foundation, in cooperation with Human Smile and Smile Givers International, provides fully equipped Medical Hub study spaces for medical students who need a quiet place, reliable electricity, and internet access.',
                'instructions' => "Applications are reviewed before acceptance. Please provide accurate and active contact details.\nUpload a clear copy of your university ID.\nAccepted students will receive joining instructions after review.",
                'submit_button' => 'Submit registration',
                'success_title' => 'Registration received',
                'success_message' => 'Thank you. Your application has been saved and will be reviewed by the Medical Hub team.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $fields = [
                ['full_name_ar', 'Full name in Arabic', 'text', null, 'Enter your full name as shown on official documents.', null],
                ['full_name_en', 'Full name in English', 'text', null, 'Enter your full name in English.', null],
                ['email', 'Email address', 'email', 'name@example.com', 'Use an active and correct email address.', null],
                ['gender', 'Gender', 'radio', null, null, ['Male', 'Female']],
                ['university', 'University', 'radio', null, null, ['Islamic University of Gaza', 'Al-Azhar University - Gaza', 'University of Israa']],
                ['academic_level', 'Academic level', 'radio', null, null, ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year', 'Sixth Year']],
                ['university_id', 'University ID number', 'text', null, null, null],
                ['university_id_document', 'University ID card', 'file', null, 'Upload a clear JPG, PNG, or PDF file. Maximum size: 10 MB.', null],
                ['whatsapp_phone', 'Mobile number with WhatsApp', 'tel', '+970', 'Include the country code.', null],
                ['alternate_phone', 'Alternative mobile number without WhatsApp', 'tel', '+970', 'Include the country code.', null],
                ['residence_status', 'Current residence status', 'radio', null, null, ['Displaced', 'Resident']],
                ['housing_type', 'Current housing type', 'radio', null, null, ['Home', 'Tent', 'Shelter center']],
                ['governorate', 'Governorate', 'radio', null, null, $isGaza ? ['Gaza City', 'North Gaza'] : ['Khan Younis', 'Middle Governorates']],
                ['current_address', 'Current residence address', 'textarea', null, 'Provide the current address in enough detail to contact you.', null],
            ];

            foreach ($fields as $index => [$key, $label, $type, $placeholder, $helpText, $options]) {
                DB::table('hub_registration_fields')->insert([
                    'hub_registration_form_id' => $formId,
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'placeholder' => $placeholder,
                    'help_text' => $helpText,
                    'options' => $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
