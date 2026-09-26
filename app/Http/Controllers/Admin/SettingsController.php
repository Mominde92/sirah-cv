<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAudit;
use App\Services\Admin\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function edit(): View
    {
        return view('admin.settings.edit', ['settings' => $this->settings]);
    }

    public function update(Request $request, AdminAudit $audit): RedirectResponse
    {
        $rules = [];

        foreach (SettingsService::DEFINITIONS as $key => $definition) {
            $rules[str_replace('.', '__', $key)] = $definition['rules'];
        }

        $validated = $request->validate($rules);
        $values = [];

        foreach (SettingsService::DEFINITIONS as $key => $definition) {
            $input = str_replace('.', '__', $key);
            $values[$key] = in_array('boolean', $definition['rules'], true) ? $request->boolean($input) : ($validated[$input] ?? null);
        }

        $this->settings->put($values);
        $audit->record($request->user(), 'settings.updated', ['keys' => array_keys($values)]);

        return back()->with('status', __('admin.settings.saved'));
    }
}
