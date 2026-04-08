<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_profile_shows_account_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Learner Sam',
            'email' => 'learner@pocketcoach.test',
        ]);

        $this->actingAs($user);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('Profile', false)
            ->assertSee('Basic info', false)
            ->assertSee('Display name', false)
            ->assertSee('Learner Sam', false)
            ->assertSee('learner@pocketcoach.test', false)
            ->assertSee('Member', false)
            ->assertSee('Save profile', false);
    }

    public function test_profile_update_saves_bio_and_social_links(): void
    {
        $user = User::factory()->create([
            'name' => 'Coach',
            'email' => 'c@example.com',
            'timezone' => 'Africa/Accra',
            'locale' => 'en',
        ]);

        $this->actingAs($user);

        $this->put('/profile', [
            'name' => 'Coach Ade',
            'headline' => 'ICF · Team effectiveness',
            'bio' => 'I help leaders ship.',
            'phone' => '+233555000111',
            'linkedin_url' => 'linkedin.com/in/coachade',
            'website_url' => 'https://coach.example',
            'twitter_url' => '',
            'avatar_url' => '',
            'timezone' => 'Africa/Lagos',
            'locale' => 'en',
        ])->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertSame('Coach Ade', $user->name);
        $this->assertSame('ICF · Team effectiveness', $user->headline);
        $this->assertSame('I help leaders ship.', $user->bio);
        $this->assertSame('+233555000111', $user->phone);
        $this->assertSame('https://linkedin.com/in/coachade', $user->linkedin_url);
        $this->assertSame('https://coach.example', $user->website_url);
        $this->assertNull($user->twitter_url);
        $this->assertSame('Africa/Lagos', $user->timezone);
    }
}
