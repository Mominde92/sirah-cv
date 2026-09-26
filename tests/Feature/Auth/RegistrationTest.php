<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee(__('auth.register.title'));
    }

    public function test_new_user_can_register_and_receives_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Sara Ahmed',
            'email' => 'Sara@Example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'sara@example.com');
        $this->assertNotNull($user);
        $this->assertSame('en', $user->locale);
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->subscription_id);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_stores_the_current_locale(): void
    {
        $this->withSession(['locale' => 'ar'])->post('/register', [
            'name' => 'سارة',
            'email' => 'sara@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'terms' => '1',
        ]);

        $this->assertSame('ar', User::firstWhere('email', 'sara@example.com')->locale);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ])->assertSessionHasErrors('password');
    }

    public function test_role_cannot_be_mass_assigned_on_registration(): void
    {
        $this->post('/register', [
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'terms' => '1',
            'role' => 'admin',
        ]);

        $this->assertFalse(User::firstWhere('email', 'mallory@example.com')->isAdmin());
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', ['email' => "user{$i}@example.com"]);
        }

        $this->post('/register', ['email' => 'one-more@example.com'])->assertStatus(429);
    }
}
