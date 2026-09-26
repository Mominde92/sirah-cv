<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DeleteAccountRequest;
use App\Services\Account\AccountDeletionService;
use App\Services\Account\AvatarService;
use App\Services\Localization\LocaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ProfileController extends Controller
{
    public function edit(Request $request, LocaleService $locales, AvatarService $avatars): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'avatarUrl' => $avatars->url($request->user()),
            'locales' => $locales->all(),
        ]);
    }

    public function destroy(DeleteAccountRequest $request, AccountDeletionService $deletion): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $deletion->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('account.deleted'));
    }
}
