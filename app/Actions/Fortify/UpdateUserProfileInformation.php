<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Account\AvatarService;
use App\Services\Localization\LocaleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

final class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(
        private readonly LocaleService $locales,
        private readonly AvatarService $avatars,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
            'locale' => ['required', 'string', Rule::in($this->locales->codes())],
            'avatar' => [
                'nullable',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000)),
            ],
            'remove_avatar' => ['sometimes', 'boolean'],
        ])->validateWithBag('updateProfileInformation');

        $emailChanged = $validated['email'] !== $user->email;

        $user->forceFill([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
            'locale' => $validated['locale'],
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (($validated['avatar'] ?? null) instanceof UploadedFile) {
            $this->avatars->replace($user, $validated['avatar']);
        } elseif (! empty($validated['remove_avatar'])) {
            $this->avatars->remove($user);
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        // The new language applies immediately, including to the redirect response.
        session()->put(LocaleService::SESSION_KEY, $user->locale);
    }
}
