<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $productSlug = $request->string('product')->trim()->value();
        $type = in_array($request->query('type'), StockMovementType::values(), true)
            ? (string) $request->query('type')
            : null;

        $movements = StockMovement::query()
            ->with(['product:id,name,slug,sku', 'user:id,name'])
            ->when($productSlug !== '', fn ($query) => $query->whereHas(
                'product',
                fn ($query) => $query->where('slug', $productSlug),
            ))
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->latestFirst()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/movements/Index', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'slug', 'sku']),
            'types' => StockMovementType::options(),
            'filters' => [
                'product' => $productSlug,
                'type' => $type,
            ],
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        StockMovement::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Movimiento registrado. El stock se recalculó automáticamente.',
        ]);
    }
}
