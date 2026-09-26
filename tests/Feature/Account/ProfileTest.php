<?php

namespace Tests\Feature\Account;

use App\Events\AccountDeleted;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
        Storage::fake('public');
    }

    public function test_profile_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/account/profile')->assertOk()->assertSee($user->email);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => 'New Name',
            'email' => $user->email,
            'locale' => 'ar',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('ar', $user->locale);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_changing_email_requires_verification_again(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => $user->name,
            'email' => 'changed@example.com',
            'locale' => 'en',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('changed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'xx',
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'locale');
    }

    public function test_avatar_can_be_uploaded_and_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'en',
            'avatar' => UploadedFile::fake()->image('me.png', 400, 400),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'en',
            'remove_avatar' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'en',
            'avatar' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'avatar');
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/password', [
            'current_password' => 'password',
            'password' => 'another-pass-42',
            'password_confirmation' => 'another-pass-42',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('another-pass-42', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct_to_change_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/password', [
            'current_password' => 'not-my-password',
            'password' => 'another-pass-42',
            'password_confirmation' => 'another-pass-42',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_account_can_be_deleted_with_password(): void
    {
        Event::fake([AccountDeleted::class]);
        $user = User::factory()->create();
        Subscription::factory()->for($user)->for(Plan::firstWhere('slug', 'pro'))->current()->create();

        $this->actingAs($user)
            ->delete('/account', ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
        Event::assertDispatched(AccountDeleted::class, fn (AccountDeleted $event) => $event->userId === $user->id);
    }

    public function test_account_is_not_deleted_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/account', ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');

        $this->assertModelExists($user);
    }
}
