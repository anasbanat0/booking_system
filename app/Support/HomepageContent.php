<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\SiteContent;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class HomepageContent
{
    public static function payload(?BookingLocation $location = null): array
    {
        $hasUsers = Schema::hasTable('users');
        $hasBookings = Schema::hasTable('bookings');
        $hasSlots = Schema::hasTable('slots');
        $hasLocations = Schema::hasTable('booking_locations');

        $content = [
            'page_title' => self::value('page_title', 'Medical Hub - Samir Foundation', $location),
            'site_logo_url' => SiteContent::getValue('site_logo_url', ''),
            'brand_title' => self::value('brand_title', 'Samir Foundation', $location),
            'brand_subtitle' => self::value('brand_subtitle', 'Medical Hub', $location),
            'hero_eyebrow' => self::value('hero_eyebrow', 'Quiet power for serious study', $location),
            'hero_title' => self::value('hero_title', 'Reserve a calm, connected seat for exams and study.', $location),
            'project_intro' => self::value('project_intro', 'A quiet, reliable Medical Hub by Samir Foundation, prepared for students who need stable electricity, internet, and a focused place to study or take online exams.', $location),
            'primary_cta_guest' => self::value('primary_cta_guest', 'Login to book', $location),
            'primary_cta_auth' => self::value('primary_cta_auth', 'Open dashboard', $location),
            'stat_students_label' => self::value('stat_students_label', 'Students', $location),
            'stat_bookings_label' => self::value('stat_bookings_label', 'Bookings', $location),
            'stat_seats_label' => self::value('stat_seats_label', 'Seats left', $location),
            'stat_branches_label' => self::value('stat_branches_label', 'Branches', $location),
            'student_card_eyebrow' => self::value('student_card_eyebrow', 'Start here', $location),
            'student_card_title' => self::value('student_card_title', 'Student access', $location),
            'student_card_description' => self::value('student_card_description', 'Sign in to reserve a prepared seat with power, internet, and a quiet environment.', $location),
            'student_card_guest_button' => self::value('student_card_guest_button', 'Continue to login', $location),
            'student_card_help_button' => self::value('student_card_help_button', 'Need help accessing your account?', $location),
            'student_card_auth_button' => self::value('student_card_auth_button', 'View weekly calendar', $location),
            'team_card_eyebrow' => self::value('team_card_eyebrow', 'Team access', $location),
            'team_card_description' => self::value('team_card_description', 'Staff manage only their assigned branch. Admins manage all branches, users, settings, imports, and exports.', $location),
            'team_staff_button' => self::value('team_staff_button', 'Staff login', $location),
            'team_admin_button' => self::value('team_admin_button', 'Admin login', $location),
            'partners_heading' => self::value('partners_heading', 'Supporters gallery', $location),
            'partners_empty_text' => self::value('partners_empty_text', 'Partner logos image can be added from the admin dashboard.', $location),
            'supporter_carousel' => self::value('supporter_carousel', '', $location),
            'supporter_gallery' => self::value('supporter_gallery', '[]', $location),
            'hub_buttons_heading' => self::value('hub_buttons_heading', 'Choose your Medical Hub', $location),
            'hub_buttons_description' => self::value('hub_buttons_description', 'Open the dedicated page for each hub to view its local details and supporters.', $location),
            'step_1_label' => self::value('step_1_label', '01', $location),
            'step_1_title' => self::value('step_1_title', 'Book ahead', $location),
            'step_1_description' => self::value('step_1_description', 'Choose your branch and weekly time before coming to the hub.', $location),
            'step_2_label' => self::value('step_2_label', '02', $location),
            'step_2_title' => self::value('step_2_title', 'Arrive prepared', $location),
            'step_2_description' => self::value('step_2_description', 'Use a calm seat with electricity and internet for studying or online exams.', $location),
            'step_3_label' => self::value('step_3_label', '03', $location),
            'step_3_title' => self::value('step_3_title', 'Manage responsibly', $location),
            'step_3_description' => self::value('step_3_description', 'Review, cancel, or reschedule from your account when changes are allowed.', $location),
            'contact_info' => self::value('contact_info', '', $location),
            'supporters' => self::value('supporters', '', $location),
            'footer_title' => self::value('footer_title', 'Samir Foundation Medical Hub', $location),
            'footer_description' => self::value('footer_description', 'A student-focused hub prepared for calm study, online exams, stable electricity, and reliable internet access.', $location),
            'footer_cta_text' => self::value('footer_cta_text', 'Need to update information or add a supporting partner announcement?', $location),
            'footer_cta_url' => self::value('footer_cta_url', '', $location),
            'footer_cta_button' => self::value('footer_cta_button', 'Open link', $location),
            'footer_contact_heading' => self::value('footer_contact_heading', 'Contact', $location),
            'footer_support_heading' => self::value('footer_support_heading', 'Support and links', $location),
            'supporters_note' => self::value('supporters_note', 'Supporting education access through prepared study spaces in Gaza and Khan Younis.', $location),
            'footer_bottom_text' => self::value('footer_bottom_text', 'Samir Foundation Medical Hub. Built for focused learning access.', $location),
            'social_links' => self::value('social_links', '', $location),
        ];

        $slotQuery = $hasSlots ? Slot::where('is_active', true) : null;
        $bookingQuery = $hasBookings ? Booking::query() : null;
        $studentQuery = $hasUsers ? User::where('role', 'student') : null;

        if ($location) {
            $slotQuery?->where('booking_location_id', $location->id);
            $bookingQuery?->whereHas('slot', fn ($query) => $query->where('booking_location_id', $location->id));
            $studentQuery?->where('booking_location_id', $location->id);
        }

        return [
            'content' => $content,
            'stats' => [
                'students' => $studentQuery?->count() ?? 0,
                'bookings' => $bookingQuery?->count() ?? 0,
                'availableSeats' => $slotQuery?->selectRaw('COALESCE(SUM(capacity - booked_count), 0) as total')->value('total') ?? 0,
                'branches' => $location ? 1 : ($hasLocations ? BookingLocation::where('is_active', true)->count() : 0),
            ],
            'locations' => $hasLocations ? BookingLocation::where('is_active', true)->orderBy('name')->get() : collect(),
            'selectedLocation' => $location,
        ];
    }

    private static function value(string $key, string $fallback, ?BookingLocation $location): string
    {
        if ($location) {
            $locationValue = SiteContent::getValue('hub_' . $location->id . '_' . $key, '');

            if ($locationValue !== '') {
                return $locationValue;
            }
        }

        return SiteContent::getValue($key, $fallback);
    }
}
