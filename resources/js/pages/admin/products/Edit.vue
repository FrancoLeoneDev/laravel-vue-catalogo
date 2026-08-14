<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ImageOff } from '@lucide/vue';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import StockMovementController from '@/actions/App/Http/Controllers/Admin/StockMovementController';
import InputError from '@/components/InputError.vue';
import StockBadge from '@/components/StockBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, formatNumber } from '@/lib/format';
import { edit, index } from '@/routes/admin/products';
import type { Category, Product, StockMovement } from '@/types/catalog';

const props = defineProps<{
    product: Product;
    currentStock: number;
    categories: Category[];
    recentMovements: StockMovement[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Productos',
                href: index(),
            },
        ],
    },
});

const fieldClass =
    'w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

/** `datetime-local` wants local wall-clock time, not the UTC ISO string. */
function localDateTimeValue(): string {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60_000)
        .toISOString()
        .slice(0, 16);
}

const defaultOccurredAt = localDateTimeValue();

const movementTypes = [
    { value: 'entrada', label: 'Entrada' },
    { value: 'salida', label: 'Salida' },
];
</script>

<template>
    <Head :title="props.product.name" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-balance">
                    {{ product.name }}
                </h1>
                <p class="mt-1 font-mono text-xs text-muted-foreground">
                    {{ product.sku }} ·
                    {{ product.category?.name ?? 'Sin rubro' }}
                </p>
            </div>

            <Link
                :href="index().url"
                class="inline-flex h-9 items-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                Volver a productos
            </Link>
        </div>

        <div
            class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:items-start"
        >
            <!-- LEFT: the product itself. -->
            <div class="rounded-lg border border-border bg-card p-5 sm:p-6">
                <h2 class="text-base font-medium">Datos del producto</h2>

                <Form
                    v-bind="ProductController.update.form(product.slug)"
                    enctype="multipart/form-data"
                    :options="{ preserveScroll: true }"
                    class="mt-6 space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="category_id">Categoría</Label>
                        <select
                            id="category_id"
                            name="category_id"
                            required
                            :class="fieldClass"
                            class="h-9 py-1"
                        >
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                                :selected="category.id === product.category_id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="errors.category_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxlength="255"
                            autocomplete="off"
                            :default-value="product.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="5000"
                            :class="fieldClass"
                            :value="product.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="price">Precio</Label>
                            <Input
                                id="price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                max="99999999.99"
                                required
                                inputmode="decimal"
                                class="tabular-nums"
                                :default-value="product.price"
                            />
                            <InputError :message="errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="sku">SKU</Label>
                            <Input
                                id="sku"
                                name="sku"
                                required
                                maxlength="64"
                                autocomplete="off"
                                class="font-mono uppercase"
                                :default-value="product.sku"
                            />
                            <InputError :message="errors.sku" />
                        </div>
                    </div>

                    <div class="grid gap-2 sm:max-w-xs">
                        <Label for="low_stock_threshold">
                            Umbral de stock bajo
                        </Label>
                        <Input
                            id="low_stock_threshold"
                            name="low_stock_threshold"
                            type="number"
                            step="1"
                            min="0"
                            max="100000"
                            required
                            inputmode="numeric"
                            class="font-mono"
                            :default-value="product.low_stock_threshold"
                        />
                        <p class="text-xs text-muted-foreground">
                            Es el umbral de alerta, no el stock. El stock sale
                            de los movimientos.
                        </p>
                        <InputError :message="errors.low_stock_threshold" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="image">Imagen</Label>

                        <div
                            v-if="product.image_path"
                            class="flex items-start gap-4 rounded-md border border-border bg-muted/30 p-3"
                        >
                            <img
                                :src="`/storage/${product.image_path}`"
                                :alt="`Imagen actual de ${product.name}`"
                                class="size-20 shrink-0 rounded-md border border-border object-cover"
                            />
                            <div class="flex flex-col gap-2 pt-1">
                                <p class="text-sm font-medium">Imagen actual</p>
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        id="remove_image"
                                        name="remove_image"
                                        value="1"
                                    />
                                    <Label
                                        for="remove_image"
                                        class="text-sm font-normal text-muted-foreground"
                                    >
                                        Quitar imagen
                                    </Label>
                                </div>
                                <InputError :message="errors.remove_image" />
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex items-center gap-2 rounded-md border border-dashed border-border p-3 text-sm text-muted-foreground"
                        >
                            <ImageOff class="size-4" aria-hidden="true" />
                            Este producto todavía no tiene imagen.
                        </div>

                        <!-- Native input: v-model on a file field is not assignable. -->
                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            :class="fieldClass"
                            class="file:mr-3 file:cursor-pointer file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground"
                        />
                        <p class="text-xs text-muted-foreground">
                            JPG, PNG o WEBP. Hasta 2 MB. Si subís una nueva,
                            reemplaza a la anterior.
                        </p>
                        <InputError :message="errors.image" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                name="is_active"
                                value="1"
                                :default-value="product.is_active"
                            />
                            <Label for="is_active">
                                Producto activo en el catálogo
                            </Label>
                        </div>
                        <InputError :message="errors.is_active" />
                    </div>

                    <div
                        class="flex items-center gap-3 border-t border-border pt-6"
                    >
                        <Button type="submit" :disabled="processing">
                            Guardar cambios
                        </Button>

                        <Link
                            :href="edit(product.slug).url"
                            class="inline-flex h-9 items-center rounded-md px-4 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            Descartar
                        </Link>
                    </div>
                </Form>
            </div>

            <!-- RIGHT: the ledger that actually decides the stock. -->
            <div class="flex flex-col gap-6">
                <section class="rounded-lg border border-border bg-card p-5">
                    <h2
                        class="font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                    >
                        Stock actual
                    </h2>

                    <p
                        class="mt-3 text-4xl font-semibold tracking-tight tabular-nums"
                    >
                        {{ formatNumber(currentStock) }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                        >
                            u.
                        </span>
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <StockBadge
                            :stock="currentStock"
                            :threshold="product.low_stock_threshold"
                            :show-units="false"
                        />
                        <span class="font-mono text-xs text-muted-foreground">
                            umbral
                            {{ formatNumber(product.low_stock_threshold) }}
                        </span>
                    </div>

                    <p class="mt-4 text-xs text-muted-foreground">
                        Es la suma de las entradas menos las salidas. No se
                        edita a mano.
                    </p>
                </section>

                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-base font-medium">Registrar movimiento</h2>

                    <Form
                        v-bind="StockMovementController.store.form()"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        class="mt-5 space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="product_id"
                            :value="product.id"
                        />

                        <div class="grid gap-2">
                            <Label for="movement_type">Tipo</Label>
                            <select
                                id="movement_type"
                                name="type"
                                required
                                :class="fieldClass"
                                class="h-9 py-1"
                            >
                                <option
                                    v-for="option in movementTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="movement_quantity">Cantidad</Label>
                            <Input
                                id="movement_quantity"
                                name="quantity"
                                type="number"
                                step="1"
                                min="1"
                                max="100000"
                                required
                                inputmode="numeric"
                                class="font-mono"
                                :default-value="1"
                            />
                            <InputError :message="errors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="movement_reason">Motivo</Label>
                            <Input
                                id="movement_reason"
                                name="reason"
                                required
                                maxlength="255"
                                autocomplete="off"
                                placeholder="Compra a proveedor, venta, ajuste…"
                            />
                            <InputError :message="errors.reason" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="movement_occurred_at">Fecha</Label>
                            <Input
                                id="movement_occurred_at"
                                name="occurred_at"
                                type="datetime-local"
                                required
                                class="font-mono"
                                :default-value="defaultOccurredAt"
                            />
                            <InputError :message="errors.occurred_at" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                        >
                            Registrar movimiento
                        </Button>
                    </Form>
                </section>

                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-base font-medium">Últimos movimientos</h2>

                    <ul
                        v-if="recentMovements.length > 0"
                        class="mt-4 divide-y divide-border"
                    >
                        <li
                            v-for="movement in recentMovements"
                            :key="movement.id"
                            class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm">
                                    {{ movement.reason }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-muted-foreground"
                                >
                                    {{ formatDateTime(movement.occurred_at) }}
                                </p>
                            </div>

                            <p
                                class="shrink-0 font-mono text-sm font-medium tabular-nums"
                                :class="
                                    movement.type === 'entrada'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{ movement.type === 'entrada' ? '+' : '−'
                                }}{{ formatNumber(movement.quantity) }}
                            </p>
                        </li>
                    </ul>

                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Todavía no hay movimientos.
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>
