<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => null,
            'type' => fake()->randomElement(StockMovementType::cases()),
            'quantity' => fake()->numberBetween(1, 50),
            'reason' => fake()->sentence(4),
            'occurred_at' => fake()->dateTimeBetween('-6 months'),
        ];
    }

    public function entrada(int $quantity = 10): self
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Entrada,
            'quantity' => $quantity,
            'reason' => 'Compra a proveedor',
        ]);
    }

    public function salida(int $quantity = 5): self
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Salida,
            'quantity' => $quantity,
            'reason' => 'Venta en mostrador',
        ]);
    }
}
