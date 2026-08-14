<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    /**
     * Public catalog. Search, category filter and pagination all run in SQL:
     * the client only ever receives the current page.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $categorySlug = $request->string('category')->trim()->value();
        $sort = in_array($request->query('sort'), ['price_asc', 'price_desc', 'newest'], true)
            ? (string) $request->query('sort')
            : 'name';

        $products = Product::query()
            ->active()
            ->with('category:id,name,slug')
            ->withCurrentStock()
            ->search($search)
            ->inCategory($categorySlug)
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('catalog/Index', [
            'products' => $products,
            'categories' => Category::query()
                ->orderBy('name')
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->loadMissing('category:id,name,slug');

        $currentStock = Product::query()
            ->withCurrentStock()
            ->whereKey($product->id)
            ->value('current_stock');

        $related = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->withCurrentStock()
            ->with('category:id,name,slug')
            ->inRandomOrder()
            ->take(4)
            ->get();

        return Inertia::render('catalog/Show', [
            'product' => $product,
            'currentStock' => (int) $currentStock,
            'related' => $related,
        ]);
    }
}
