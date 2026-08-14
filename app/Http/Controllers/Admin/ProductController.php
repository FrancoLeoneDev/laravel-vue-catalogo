<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $categorySlug = $request->string('category')->trim()->value();
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? (string) $request->query('status')
            : null;

        $products = Product::query()
            ->with('category:id,name,slug')
            ->withCurrentStock()
            ->search($search)
            ->inCategory($categorySlug)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/products/Index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/Create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::query()->create($data);

        return to_route('admin.products.edit', $product)->with('toast', [
            'type' => 'success',
            'message' => 'Producto creado. Registrá un movimiento para darle stock inicial.',
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->loadMissing('category:id,name,slug');

        $currentStock = Product::query()
            ->withCurrentStock()
            ->whereKey($product->id)
            ->value('current_stock');

        return Inertia::render('admin/products/Edit', [
            'product' => $product,
            'currentStock' => (int) $currentStock,
            'categories' => $this->categoryOptions(),
            'recentMovements' => $product->stockMovements()
                ->latestFirst()
                ->take(10)
                ->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($product);
            $data['image_path'] = null;
        }

        $product->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Producto actualizado.',
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteImage($product);

        // Movements cascade with the product: the ledger only exists to explain
        // a product's stock, so it should not outlive it.
        $product->delete();

        return to_route('admin.products.index')->with('toast', [
            'type' => 'success',
            'message' => 'Producto eliminado.',
        ]);
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image_path !== null) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categoryOptions(): \Illuminate\Support\Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name', 'slug']);
    }
}
