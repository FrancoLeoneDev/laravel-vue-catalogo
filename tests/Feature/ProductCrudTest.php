<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

/**
 * A complete, valid payload for the product form. Note it never carries a
 * `slug` (the Form Request derives it) nor a `stock` (there is no such thing).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productPayload(Category $category, array $overrides = []): array
{
    return [
        'category_id' => $category->id,
        'name' => 'Taladro Percutor Bosch',
        'description' => 'Taladro percutor de 650W con maletín.',
        'price' => '45990.00',
        'sku' => 'tal-0001',
        'is_active' => true,
        'low_stock_threshold' => 5,
        ...$overrides,
    ];
}

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

it('redirige al login a los invitados que entran al panel', function (string $method, string $route): void {
    $product = Product::factory()->create();

    $url = str_contains($route, 'products.edit') || str_contains($route, 'products.update') || str_contains($route, 'products.destroy')
        ? route($route, $product)
        : route($route);

    $this->{$method}($url)->assertRedirect(route('login'));
})->with([
    'listado' => ['get', 'admin.products.index'],
    'alta' => ['get', 'admin.products.create'],
    'guardar' => ['post', 'admin.products.store'],
    'edición' => ['get', 'admin.products.edit'],
    'actualizar' => ['put', 'admin.products.update'],
    'eliminar' => ['delete', 'admin.products.destroy'],
    'categorías' => ['get', 'admin.categories.index'],
    'movimientos' => ['get', 'admin.movements.index'],
]);

/*
|--------------------------------------------------------------------------
| CRUD
|--------------------------------------------------------------------------
*/

it('lista los productos con su stock derivado', function (): void {
    $products = Product::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/products/Index')
            ->has('products.data', 3)
            ->where('products.data.0.current_stock', 0));

    expect($products)->toHaveCount(3);
});

it('permite a un usuario autenticado crear un producto', function (): void {
    $category = Category::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.products.store'), productPayload($category));

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', [
        'category_id' => $category->id,
        'name' => 'Taladro Percutor Bosch',
        'slug' => 'taladro-percutor-bosch',
        'sku' => 'TAL-0001',
        'price' => '45990.00',
        'is_active' => true,
        'low_stock_threshold' => 5,
    ]);
});

it('permite actualizar un producto existente', function (): void {
    $product = Product::factory()->create(['name' => 'Martillo viejo']);
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('admin.products.edit', $product))
        ->put(route('admin.products.update', $product), productPayload($category, [
            'name' => 'Martillo Carpintero 500g',
            'sku' => 'mar-0500',
            'price' => '12500.00',
            'low_stock_threshold' => 3,
        ]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'category_id' => $category->id,
        'name' => 'Martillo Carpintero 500g',
        'slug' => 'martillo-carpintero-500g',
        'sku' => 'MAR-0500',
        'price' => '12500.00',
        'low_stock_threshold' => 3,
    ]);
});

it('permite eliminar un producto', function (): void {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'));

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

/*
|--------------------------------------------------------------------------
| Stock can never come from the product form
|--------------------------------------------------------------------------
*/

it('ignora cualquier campo de stock enviado en el formulario', function (): void {
    $category = Category::factory()->create();

    expect(Schema::hasColumn('products', 'stock'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'current_stock'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'quantity'))->toBeFalse();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.products.store'), productPayload($category, [
            'stock' => 999,
            'current_stock' => 999,
        ]))
        ->assertSessionHasNoErrors();

    $product = Product::query()->where('slug', 'taladro-percutor-bosch')->sole();

    expect($product->getAttributes())->not->toHaveKey('stock')
        ->and((int) Product::query()->withCurrentStock()->whereKey($product->id)->value('current_stock'))->toBe(0)
        ->and($product->stockMovements()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('exige los campos obligatorios del producto', function (): void {
    $this->actingAs(User::factory()->create())
        ->from(route('admin.products.create'))
        ->post(route('admin.products.store'), [])
        ->assertSessionHasErrors(['category_id', 'name', 'slug', 'price', 'sku', 'low_stock_threshold']);

    expect(Product::query()->count())->toBe(0);
});

it('rechaza un SKU duplicado', function (): void {
    $category = Category::factory()->create();
    Product::factory()->create(['sku' => 'TAL-0001']);

    $this->actingAs(User::factory()->create())
        ->from(route('admin.products.create'))
        // Lower case on purpose: the Form Request upper-cases it before the unique check.
        ->post(route('admin.products.store'), productPayload($category, ['sku' => 'tal-0001']))
        ->assertSessionHasErrors('sku');

    expect(Product::query()->count())->toBe(1);
});

it('rechaza un slug duplicado derivado del nombre', function (): void {
    $category = Category::factory()->create();
    Product::factory()->create(['slug' => 'taladro-percutor-bosch']);

    $this->actingAs(User::factory()->create())
        ->from(route('admin.products.create'))
        ->post(route('admin.products.store'), productPayload($category, ['name' => 'Taladro Percutor Bosch']))
        ->assertSessionHasErrors('slug');

    expect(Product::query()->count())->toBe(1);
});

it('rechaza un precio negativo', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('admin.products.create'))
        ->post(route('admin.products.store'), productPayload($category, ['price' => '-1']))
        ->assertSessionHasErrors('price');

    expect(Product::query()->count())->toBe(0);
});

it('deriva el slug del nombre sin recibir un campo slug', function (): void {
    $category = Category::factory()->create();
    $payload = productPayload($category, ['name' => 'Amoladora Angular 4½"']);

    expect($payload)->not->toHaveKey('slug');

    $this->actingAs(User::factory()->create())
        ->post(route('admin.products.store'), $payload)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', ['slug' => 'amoladora-angular-4']);
});

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

it('no elimina una categoría que todavía tiene productos', function (): void {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $this->actingAs(User::factory()->create())
        ->from(route('admin.categories.index'))
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('toast.type', 'error');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('elimina una categoría vacía', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

/*
|--------------------------------------------------------------------------
| Public catalog
|--------------------------------------------------------------------------
*/

it('filtra el catálogo por nombre desde el servidor', function (): void {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id, 'name' => 'Martillo Carpintero']);
    Product::factory()->create(['category_id' => $category->id, 'name' => 'Destornillador Phillips']);

    $this->get(route('home', ['search' => 'martillo']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Martillo Carpintero')
            ->where('filters.search', 'martillo'));
});

it('filtra el catálogo por categoría', function (): void {
    $herramientas = Category::factory()->create(['slug' => 'herramientas']);
    $pinturas = Category::factory()->create(['slug' => 'pinturas']);

    Product::factory()->count(2)->create(['category_id' => $herramientas->id]);
    Product::factory()->create(['category_id' => $pinturas->id]);

    $this->get(route('home', ['category' => 'herramientas']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 2)
            ->where('products.data.0.category.slug', 'herramientas')
            ->where('products.data.1.category.slug', 'herramientas'));
});

it('pagina el catálogo con el tamaño de página configurado', function (): void {
    Product::factory()->count(15)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 12)
            ->where('products.total', 15)
            ->where('products.per_page', 12));

    $this->get(route('home', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 3));
});

it('nunca muestra productos inactivos en el catálogo', function (): void {
    $category = Category::factory()->create();
    $visible = Product::factory()->create(['category_id' => $category->id]);
    $hidden = Product::factory()->inactive()->create(['category_id' => $category->id]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $visible->id));

    // Nor by searching for it, nor on its own detail page.
    $this->get(route('home', ['search' => $hidden->name]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 0));

    $this->get(route('catalog.show', $hidden))->assertNotFound();
});
