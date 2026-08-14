<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $sku
 * @property bool $is_active
 * @property int $low_stock_threshold
 * @property string|null $image_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $current_stock Only present when the query used scopeWithCurrentStock().
 */
#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'price',
    'sku',
    'is_active',
    'low_stock_threshold',
    'image_path',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'low_stock_threshold' => 'integer',
            'current_stock' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Attach the derived stock balance as a correlated subquery.
     *
     * Stock is never stored on the product: it is the signed sum of every
     * movement, so one extra subquery keeps the whole listing at a single
     * round trip instead of N queries.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithCurrentStock(Builder $query): Builder
    {
        return $query->addSelect([
            'current_stock' => StockMovement::query()
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE -quantity END), 0)',
                    [StockMovementType::Entrada->value],
                )
                ->whereColumn('stock_movements.product_id', 'products.id'),
        ]);
    }

    /**
     * Products whose derived stock has fallen to or below their threshold.
     *
     * The comparison repeats the subquery in a WHERE rather than filtering the
     * `current_stock` alias with HAVING. A HAVING without GROUP BY is a MySQL
     * extension that SQLite rejects outright, and it would also break count(),
     * which the dashboard relies on.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->withCurrentStock()->whereRaw(
            '(select coalesce(sum(case when type = ? then quantity else -quantity end), 0)'
            .' from stock_movements where stock_movements.product_id = products.id)'
            .' <= products.low_stock_threshold',
            [StockMovementType::Entrada->value],
        );
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Case-insensitive name / SKU search, applied server side.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->where('name', 'like', "%{$escaped}%")
                ->orWhere('sku', 'like', "%{$escaped}%");
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeInCategory(Builder $query, ?string $categorySlug): Builder
    {
        if (blank($categorySlug)) {
            return $query;
        }

        return $query->whereHas(
            'category',
            fn (Builder $query) => $query->where('slug', $categorySlug),
        );
    }
}
