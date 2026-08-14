<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Derived stock
|--------------------------------------------------------------------------
|
| Stock is never a column. It is the signed sum of every StockMovement of a
| product: `entrada` adds, `salida` subtracts. These tests exist to keep that
| invariant from quietly regressing into a denormalised counter.
|
*/

it('calcula el stock como la suma con signo de los movimientos', function (): void {
    $product = Product::factory()->create();

    StockMovement::factory()->entrada(40)->create(['product_id' => $product->id]);
    StockMovement::factory()->entrada(10)->create(['product_id' => $product->id]);
    StockMovement::factory()->salida(15)->create(['product_id' => $product->id]);

    $stock = Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock');

    expect((int) $stock)->toBe(35);
});

it('devuelve stock cero para un producto sin movimientos', function (): void {
    $product = Product::factory()->create();

    $stock = Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock');

    // The COALESCE path: SUM() over an empty set is NULL, not 0.
    expect($stock)->not->toBeNull()
        ->and((int) $stock)->toBe(0);
});

it('coincide con la suma en PHP de signedQuantity para varios productos', function (): void {
    $products = Product::factory()->count(4)->create();

    foreach ($products as $index => $product) {
        StockMovement::factory()
            ->count($index + 2)
            ->create(['product_id' => $product->id]);
    }

    $derived = Product::query()->withCurrentStock()->pluck('current_stock', 'id');

    $expected = $products->mapWithKeys(fn (Product $product): array => [
        $product->id => $product->stockMovements()
            ->get()
            ->sum(fn (StockMovement $movement): int => $movement->signedQuantity()),
    ]);

    expect($derived->toArray())->toBe($expected->toArray());
});

it('resuelve el stock de un listado sin consultas N+1', function (): void {
    Product::factory()->count(3)->create()
        ->each(fn (Product $product) => StockMovement::factory()->entrada(7)->create(['product_id' => $product->id]));

    DB::flushQueryLog();
    DB::enableQueryLog();
    $small = Product::query()->withCurrentStock()->with('category:id,name,slug')->get();
    $queriesForThree = count(DB::getQueryLog());
    DB::disableQueryLog();

    Product::factory()->count(27)->create()
        ->each(fn (Product $product) => StockMovement::factory()->entrada(7)->create(['product_id' => $product->id]));

    DB::flushQueryLog();
    DB::enableQueryLog();
    $large = Product::query()->withCurrentStock()->with('category:id,name,slug')->get();
    $queriesForThirty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($small)->toHaveCount(3)
        ->and($large)->toHaveCount(30)
        // One query for the products (stock arrives in the correlated subquery),
        // one for the eager-loaded categories. Constant, whatever N is.
        ->and($queriesForThree)->toBe(2)
        ->and($queriesForThirty)->toBe($queriesForThree)
        ->and($large->every(fn (Product $product): bool => $product->current_stock === 7))->toBeTrue();
});

it('scopeLowStock devuelve exactamente los productos en o por debajo de su umbral', function (): void {
    $below = Product::factory()->create(['low_stock_threshold' => 10]);
    $equal = Product::factory()->create(['low_stock_threshold' => 10]);
    $above = Product::factory()->create(['low_stock_threshold' => 10]);
    $empty = Product::factory()->create(['low_stock_threshold' => 0]);

    StockMovement::factory()->entrada(4)->create(['product_id' => $below->id]);
    StockMovement::factory()->entrada(10)->create(['product_id' => $equal->id]);
    StockMovement::factory()->entrada(11)->create(['product_id' => $above->id]);

    $lowStock = Product::query()->lowStock()->pluck('id');

    expect($lowStock->all())->toEqualCanonicalizing([$below->id, $equal->id, $empty->id])
        ->and($lowStock)->not->toContain($above->id);
});

it('el panel carga y reporta los productos con stock bajo', function (): void {
    $low = Product::factory()->create(['low_stock_threshold' => 10]);
    StockMovement::factory()->entrada(2)->create(['product_id' => $low->id]);

    $healthy = Product::factory()->create(['low_stock_threshold' => 5]);
    StockMovement::factory()->entrada(80)->create(['product_id' => $healthy->id]);

    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Dashboard')
            ->where('stats.lowStockCount', 1)
            ->where('stats.unitsInStock', 82)
            ->has('lowStock', 1));
});

it('scopeLowStock se puede contar, que es lo que usa el panel', function (): void {
    // Regression: the scope used to filter the `current_stock` select alias with
    // HAVING, which is a MySQL-only extension. It aborted on SQLite and made
    // count() return a per-group count instead of the total.
    $product = Product::factory()->create(['low_stock_threshold' => 10]);
    StockMovement::factory()->entrada(3)->create(['product_id' => $product->id]);

    $healthy = Product::factory()->create(['low_stock_threshold' => 1]);
    StockMovement::factory()->entrada(50)->create(['product_id' => $healthy->id]);

    expect(Product::query()->lowStock()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Registering movements through the panel
|--------------------------------------------------------------------------
*/

it('rechaza una salida mayor al stock disponible', function (): void {
    $product = Product::factory()->create();
    StockMovement::factory()->entrada(8)->create(['product_id' => $product->id]);

    $response = $this->actingAs(User::factory()->create())
        ->from(route('admin.movements.index'))
        ->post(route('admin.movements.store'), [
            'product_id' => $product->id,
            'type' => 'salida',
            'quantity' => 9,
            'reason' => 'Venta en mostrador',
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHasErrors('quantity');

    expect(StockMovement::query()->where('type', 'salida')->count())->toBe(0)
        ->and((int) Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock'))->toBe(8);
});

it('acepta una salida exactamente igual al stock disponible', function (): void {
    $product = Product::factory()->create();
    StockMovement::factory()->entrada(8)->create(['product_id' => $product->id]);

    $response = $this->actingAs(User::factory()->create())
        ->from(route('admin.movements.index'))
        ->post(route('admin.movements.store'), [
            'product_id' => $product->id,
            'type' => 'salida',
            'quantity' => 8,
            'reason' => 'Venta en mostrador',
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHasNoErrors();

    expect((int) Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock'))->toBe(0);
});

it('registra el movimiento con el usuario autenticado y actualiza el stock derivado al instante', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $originalUpdatedAt = $product->updated_at;

    expect(Schema::hasColumn('products', 'stock'))->toBeFalse();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->from(route('admin.movements.index'))
        ->post(route('admin.movements.store'), [
            'product_id' => $product->id,
            'type' => 'entrada',
            'quantity' => 25,
            'reason' => 'Compra a proveedor',
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasNoErrors();

    $writesToProducts = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => (bool) preg_match('/^\s*update\s+"products"/i', (string) $query['query']));
    DB::disableQueryLog();

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'type' => 'entrada',
        'quantity' => 25,
    ]);

    expect($writesToProducts)->toBeEmpty()
        ->and((int) Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock'))->toBe(25)
        ->and($product->fresh()->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});
