<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('site_contents')->insert([
            [
                'key' => 'project_intro',
                'value' => 'منصة حجز أسبوعية تساعد الطلاب على اختيار الفرع والفترة المناسبة بسهولة، مع إدارة عادلة للسعة وعدد الحجوزات.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'usage_instructions',
                'value' => "1. سجل الدخول إلى حسابك.\n2. افتح التقويم الأسبوعي.\n3. اختر الفرع والفترة المناسبة.\n4. تابع حجوزاتك من صفحة حجوزاتي.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_info',
                'value' => "غزة - مؤسسة سمير\n0599000000\ninfo@example.org",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'supporters',
                'value' => 'الشريك المجتمعي, الجهة الداعمة, البرنامج التعليمي',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'social_links',
                'value' => "Facebook: https://facebook.com\nInstagram: https://instagram.com\nWhatsApp: https://wa.me/970599000000",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
