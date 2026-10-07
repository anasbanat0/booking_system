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

    public function test_homepage_shows_the_default_registration_link_for_each_hub(): void
    {
        $gaza = BookingLocation::query()->where('slug', 'gaza')->firstOrFail();
        SiteContent::updateOrCreate(
            ['key' => 'hub_'.$gaza->id.'_registration_button_label'],
            ['value' => 'Register for '.$gaza->name.' Hub']
        );

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('https://forms.gle/GgCcdxxgnzUMq8917', false)
            ->assertSee('https://forms.gle/SkBLXMDVkS8uNqyy7', false)
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
                'hub_'.$gaza->id.'_registration_url' => 'https://forms.gle/custom-gaza-form',
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(
            'https://forms.gle/custom-gaza-form',
            SiteContent::getValue('hub_'.$gaza->id.'_registration_url')
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('Apply for Gaza')
            ->assertSee('https://forms.gle/custom-gaza-form', false);
    }

    public function test_admin_cannot_save_an_invalid_registration_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gaza = BookingLocation::query()->where('slug', 'gaza')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.content.index'))
            ->patch(route('admin.content.update'), [
                'content' => [
                    'hub_'.$gaza->id.'_registration_url' => 'not-a-url',
                ],
            ])
            ->assertRedirect(route('admin.content.index'))
            ->assertSessionHasErrors('content.hub_'.$gaza->id.'_registration_url');
    }
}
