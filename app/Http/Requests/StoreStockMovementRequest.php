<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockMovementRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (blank($this->input('occurred_at'))) {
            $this->merge(['occurred_at' => now()->format('Y-m-d\TH:i')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * A salida can never take the derived balance below zero, so the ledger
     * stays a truthful history rather than something that needs correcting.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->enum('type', StockMovementType::class) !== StockMovementType::Salida) {
                    return;
                }

                $product = Product::query()
                    ->withCurrentStock()
                    ->find($this->integer('product_id'));

                if ($product === null) {
                    return;
                }

                if ($this->integer('quantity') > $product->current_stock) {
                    $validator->errors()->add(
                        'quantity',
                        "No hay stock suficiente: disponible {$product->current_stock} unidades.",
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'type' => 'tipo',
            'quantity' => 'cantidad',
            'reason' => 'motivo',
            'occurred_at' => 'fecha',
        ];
    }
}
