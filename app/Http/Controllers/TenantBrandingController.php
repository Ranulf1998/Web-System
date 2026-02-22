<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantBrandingController extends Controller
{
    public function edit(): View
    {
        $tenant = tenant();
        $branding = $tenant->settings['branding'] ?? [];

        return view('tenant.branding', [
            'branding' => $branding,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $data = $request->validate([
            'primary_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'accent_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'background_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $settings = $tenant->settings ?? [];
        $branding = $settings['branding'] ?? [];

        if (!empty($data['primary_color'])) {
            $branding['primary'] = $data['primary_color'];
        }

        if (!empty($data['accent_color'])) {
            $branding['accent'] = $data['accent_color'];
        }

        if (!empty($data['background_color'])) {
            $branding['background'] = $data['background_color'];
        }

        if ($request->boolean('remove_logo') && !empty($branding['logo_path'])) {
            Storage::disk('public')->delete($branding['logo_path']);
            unset($branding['logo_path']);
        }

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $folder = 'tenant_' . $tenant->id . '/branding';
            $filename = 'logo.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs($folder, $filename, 'public');

            if (!empty($branding['logo_path']) && $branding['logo_path'] !== $path) {
                Storage::disk('public')->delete($branding['logo_path']);
            }

            $branding['logo_path'] = $path;
        }

        $settings['branding'] = $branding;
        $tenant->settings = $settings;
        $tenant->save();

        return redirect()
            ->route('branding.edit')
            ->with('status', 'Branding updated.');
    }
}