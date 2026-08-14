<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $lowStock = Product::query()
            ->lowStock()
            ->with('category:id,name,slug')
            ->orderBy('name')
            ->take(8)
            ->get();

        // One aggregate pass over the ledger instead of summing in PHP.
        $unitsInStock = (int) DB::table('stock_movements')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE -quantity END), 0) as units',
                [StockMovementType::Entrada->value],
            )
            ->value('units');

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'products' => Product::query()->count(),
                'activeProducts' => Product::query()->active()->count(),
                'categories' => Category::query()->count(),
                'unitsInStock' => $unitsInStock,
                'lowStockCount' => Product::query()->lowStock()->count(),
                'movementsLast30Days' => StockMovement::query()
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->count(),
            ],
            'lowStock' => $lowStock,
            'recentMovements' => StockMovement::query()
                ->with(['product:id,name,slug,sku', 'user:id,name'])
                ->latestFirst()
                ->take(10)
                ->get(),
        ]);
    }
}
