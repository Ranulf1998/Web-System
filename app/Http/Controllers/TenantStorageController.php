<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantStorageController extends Controller
{
    public function show(string $subdomain, string $path): StreamedResponse
    {
        $tenant = tenant();
        if (!$tenant) {
            abort(404);
        }

        $path = ltrim($path, '/');
        $prefix = 'tenant_' . $tenant->id . '/';
        if (!Str::startsWith($path, $prefix)) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $mimeType = Storage::disk('public')->mimeType($path);
        
        return response()->stream(function () use ($path) {
            echo file_get_contents(Storage::disk('public')->path($path));
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
