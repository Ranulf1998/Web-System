<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantBrandingController extends Controller
{
    public function edit(): View
    {
        abort_unless(tenant()->canUseFeature('branding'), 403, 'Branding customization is not available on your current plan.');
        
        $tenant = tenant();
        $branding = $tenant->settings['branding'] ?? [];

        return view('tenant.branding', [
            'branding' => $branding,
            'tenant' => $tenant,
            'canUpdateShopName' => Auth::user()?->hasRole('Owner') ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(tenant()->canUseFeature('branding'), 403, 'Branding customization is not available on your current plan.');
        
        $tenant = tenant();
        $logoChanged = false;

        $data = $request->validate([
            'shop_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'accent_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'background_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $shopNameChanged = false;

        if (array_key_exists('shop_name', $data)) {
            if (! (Auth::user()?->hasRole('Owner') ?? false)) {
                abort(403, 'Only the shop owner can change the shop name.');
            }

            $newShopName = trim((string) ($data['shop_name'] ?? ''));

            if ($newShopName === '') {
                return redirect()->route('branding.edit')->withErrors([
                    'shop_name' => 'Shop name is required.',
                ]);
            }

            if ($tenant->name !== $newShopName) {
                $tenant->name = $newShopName;
                $shopNameChanged = true;
            }
        }

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
            $logoChanged = true;
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
            $logoChanged = true;
        }

        $settings['branding'] = $branding;
        $tenant->settings = $settings;
        $tenant->save();

        ActivityLogger::log(
            'branding.updated',
            'Updated tenant branding settings',
            $tenant,
            [
                'primary_color' => $branding['primary'] ?? null,
                'accent_color' => $branding['accent'] ?? null,
                'background_color' => $branding['background'] ?? null,
                'logo_changed' => $logoChanged,
                'shop_name_changed' => $shopNameChanged,
                'shop_name' => $tenant->name,
            ]
        );

        return redirect()
            ->route('branding.edit')
            ->with('status', 'Branding updated.');
    }
}