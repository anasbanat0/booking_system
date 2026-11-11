<?php

namespace Tests\Feature;

use App\Models\BookingLocation;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageRegistrationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_internal_registration_link_for_each_hub(): void
    {
        $gaza = BookingLocation::query()->where('slug', 'gaza')->firstOrFail();
        SiteContent::updateOrCreate(
            ['key' => 'hub_'.$gaza->id.'_registration_button_label'],
            ['value' => 'Register for '.$gaza->name.' Hub']
        );

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('hub-registration.show', $gaza), false)
            ->assertSee(route('hub-registration.show', BookingLocation::where('slug', 'khan-younis')->firstOrFail()), false)
            ->assertDontSee('forms.gle', false)
            ->assertDontSee('docs.google.com/forms', false)
            ->assertSee('Register')
            ->assertDontSee('Register for '.$gaza->name.' Hub');
    }

    public function test_admin_can_customize_a_hub_registration_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gaza = BookingLocation::query()->where('slug', 'gaza')->firstOrFail();

        $response = $this->actingAs($admin)->patch(route('admin.content.update'), [
            'content' => [
                'hub_'.$gaza->id.'_registration_button_label' => 'Apply for Gaza',
            ],
        ]);

        $response->assertRedirect();
        $this->get('/')
            ->assertOk()
            ->assertSee('Apply for Gaza')
            ->assertSee(route('hub-registration.show', $gaza), false);
    }
}
