<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Note there is deliberately no `stock` rule here: stock is derived from
     * StockMovement records and can only change by registering a movement.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'is_active' => ['required', 'boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'name' => 'nombre',
            'slug' => 'slug',
            'description' => 'descripción',
            'price' => 'precio',
            'sku' => 'SKU',
            'is_active' => 'estado',
            'low_stock_threshold' => 'umbral de stock bajo',
            'image' => 'imagen',
        ];
    }
}
