<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view products|manage products')->only(['index']);
        $this->middleware('permission:manage products')->except(['index']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('tenant_' . tenant()->id . '/products', 'public');
        }

        $product = Product::create($data);

        ActivityLogger::log(
            'product.created',
            'Created product ' . $product->name,
            $product
        );

        return redirect()->route('products.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Product created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if (!empty($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('tenant_' . tenant()->id . '/products', 'public');
        }

        $product->update($data);

        ActivityLogger::log(
            'product.updated',
            'Updated product ' . $product->name,
            $product
        );

        return redirect()->route('products.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Product updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $productName = $product->name;

        if (!empty($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        ActivityLogger::log(
            'product.deleted',
            'Deleted product ' . $productName,
            null,
            ['name' => $productName]
        );

        return redirect()->route('products.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Product deleted');
    }
}
