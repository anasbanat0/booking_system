<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contents = [
            'page_title' => 'Medical Hub - Samir Foundation',
            'brand_title' => 'Samir Foundation',
            'brand_subtitle' => 'Medical Hub',
            'hero_eyebrow' => 'Quiet power for serious study',
            'hero_title' => 'Reserve a calm, connected seat for exams and study.',
            'primary_cta_guest' => 'Login to book',
            'primary_cta_auth' => 'Open dashboard',
            'stat_students_label' => 'Students',
            'stat_bookings_label' => 'Bookings',
            'stat_seats_label' => 'Seats left',
            'stat_branches_label' => 'Branches',
            'student_card_eyebrow' => 'Start here',
            'student_card_title' => 'Student access',
            'student_card_description' => 'Sign in to reserve a prepared seat with power, internet, and a quiet environment.',
            'student_card_guest_button' => 'Continue to login',
            'student_card_help_button' => 'Need help accessing your account?',
            'student_card_auth_button' => 'View weekly calendar',
            'team_card_eyebrow' => 'Team access',
            'team_card_description' => 'Staff manage only their assigned branch. Admins manage all branches, users, settings, imports, and exports.',
            'team_staff_button' => 'Staff login',
            'team_admin_button' => 'Admin login',
            'partners_heading' => 'Supporting partners',
            'partners_empty_text' => 'Partner logos image can be added from the public images folder.',
            'step_1_label' => '01',
            'step_1_title' => 'Book ahead',
            'step_1_description' => 'Choose your branch and weekly time before coming to the hub.',
            'step_2_label' => '02',
            'step_2_title' => 'Arrive prepared',
            'step_2_description' => 'Use a calm seat with electricity and internet for studying or online exams.',
            'step_3_label' => '03',
            'step_3_title' => 'Manage responsibly',
            'step_3_description' => 'Review, cancel, or reschedule from your account when changes are allowed.',
            'footer_cta_button' => 'Open link',
            'footer_contact_heading' => 'Contact',
            'footer_support_heading' => 'Support and links',
            'footer_bottom_text' => 'Samir Foundation Medical Hub. Built for focused learning access.',
        ];

        foreach ($contents as $key => $value) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->whereIn('key', [
                'page_title',
                'brand_title',
                'brand_subtitle',
                'hero_eyebrow',
                'hero_title',
                'primary_cta_guest',
                'primary_cta_auth',
                'stat_students_label',
                'stat_bookings_label',
                'stat_seats_label',
                'stat_branches_label',
                'student_card_eyebrow',
                'student_card_title',
                'student_card_description',
                'student_card_guest_button',
                'student_card_help_button',
                'student_card_auth_button',
                'team_card_eyebrow',
                'team_card_description',
                'team_staff_button',
                'team_admin_button',
                'partners_heading',
                'partners_empty_text',
                'step_1_label',
                'step_1_title',
                'step_1_description',
                'step_2_label',
                'step_2_title',
                'step_2_description',
                'step_3_label',
                'step_3_title',
                'step_3_description',
                'footer_cta_button',
                'footer_contact_heading',
                'footer_support_heading',
                'footer_bottom_text',
            ])
            ->delete();
    }
};
