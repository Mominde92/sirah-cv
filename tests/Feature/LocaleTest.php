<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_english_is_the_default_and_ltr(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Language', 'en')
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    public function test_browser_preference_is_used_for_guests(): void
    {
        $this->withHeader('Accept-Language', 'ar-AE,ar;q=0.9,en;q=0.8')
            ->get('/')
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('dir="rtl"', false);
    }

    public function test_guest_can_switch_language(): void
    {
        $this->from('/')->post('/locale/ar')->assertRedirect('/');

        $this->get('/')
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee(__('landing.hero.title', locale: 'ar'));
    }

    public function test_switching_language_is_saved_on_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post('/locale/ar');

        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_user_preference_wins_over_the_browser(): void
    {
        $user = User::factory()->arabic()->create();

        $this->actingAs($user)
            ->withHeader('Accept-Language', 'en-US')
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar');
    }

    public function test_unsupported_locale_returns_not_found(): void
    {
        $this->post('/locale/fr')->assertNotFound();
    }

    public function test_arabic_validation_messages_are_translated(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'حقل البريد الإلكتروني مطلوب.']);
    }
}
