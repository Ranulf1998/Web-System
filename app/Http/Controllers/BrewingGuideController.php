<?php

namespace App\Http\Controllers;

use App\Models\BrewingGuide;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrewingGuideController extends Controller
{
    use AuthorizesRequests;
    public function index(): View
    {
        $guides = BrewingGuide::orderBy('created_at', 'desc')->paginate(12);
        return view('brewing-guides.index', compact('guides'));
    }

    public function show(string $subdomain, BrewingGuide $brewingGuide): View
    {
        return view('brewing-guides.show', compact('brewingGuide'));
    }

    public function create(): View
    {
        $this->authorize('manage users');
        return view('brewing-guides.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage users');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*' => 'required|string',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'prep_time' => 'nullable|integer|min:0',
            'brew_time' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|in:easy,medium,hard',
        ]);

        $data['tenant_id'] = tenant()->id;

        // Filter empty ingredients
        if (!empty($data['ingredients'])) {
            $data['ingredients'] = array_values(array_filter($data['ingredients']));
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $folder = 'tenant_' . tenant()->id . '/brewing-guides';
            $filename = uniqid('guide_') . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($folder, $filename, 'public');
            $data['image_path'] = $path;
        }

        BrewingGuide::create($data);

        return redirect()
            ->route('brewing-guides.index')
            ->with('status', 'Brewing guide created successfully.');
    }

    public function edit(string $subdomain, BrewingGuide $brewingGuide): View
    {
        $this->authorize('manage users');
        return view('brewing-guides.edit', compact('brewingGuide'));
    }

    public function update(Request $request, string $subdomain, BrewingGuide $brewingGuide): RedirectResponse
    {
        $this->authorize('manage users');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*' => 'required|string',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'prep_time' => 'nullable|integer|min:0',
            'brew_time' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|in:easy,medium,hard',
        ]);

        // Filter empty ingredients
        if (!empty($data['ingredients'])) {
            $data['ingredients'] = array_values(array_filter($data['ingredients']));
        }

        if ($request->boolean('remove_image') && $brewingGuide->image_path) {
            Storage::disk('public')->delete($brewingGuide->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($brewingGuide->image_path) {
                Storage::disk('public')->delete($brewingGuide->image_path);
            }
            $image = $request->file('image');
            $folder = 'tenant_' . tenant()->id . '/brewing-guides';
            $filename = uniqid('guide_') . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($folder, $filename, 'public');
            $data['image_path'] = $path;
        }

        $brewingGuide->update($data);

        return redirect()
            ->route('brewing-guides.show', $brewingGuide)
            ->with('status', 'Brewing guide updated successfully.');
    }

    public function destroy(string $subdomain, BrewingGuide $brewingGuide): RedirectResponse
    {
        $this->authorize('manage users');

        if ($brewingGuide->image_path) {
            Storage::disk('public')->delete($brewingGuide->image_path);
        }

        $brewingGuide->delete();

        return redirect()
            ->route('brewing-guides.index')
            ->with('status', 'Brewing guide deleted successfully.');
    }
}
