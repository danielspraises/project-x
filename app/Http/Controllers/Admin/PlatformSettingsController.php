<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        $branding = PlatformSetting::get('branding', [
            'primary_color' => '#1f2937',
            'secondary_color' => null,
        ]);

        return view('admin.settings.edit', compact('branding'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        PlatformSetting::set('branding', [
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'] ?? null,
        ]);

        return redirect()->route('admin.settings.edit')->with('success', 'Platform branding updated.');
    }
}
