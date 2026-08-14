<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { PackagePlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import StockMovementController from '@/actions/App/Http/Controllers/Admin/StockMovementController';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, formatNumber } from '@/lib/format';
import { index } from '@/routes/admin/movements';
import type { Paginated, Product, StockMovement } from '@/types/catalog';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Movimientos de stock', href: index() }],
    },
});

const props = defineProps<{
    movements: Paginated<StockMovement>;
    products: Pick<Product, 'id' | 'name' | 'slug' | 'sku'>[];
    types: { value: string; label: string }[];
    filters: { product: string; type: string | null };
}>();

/** Shared native-control styling so selects line up with the shadcn Input height. */
const controlClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';

const typeLabels = computed<Record<string, string>>(() =>
    Object.fromEntries(props.types.map((type) => [type.value, type.label])),
);

const hasFilters = computed(
    () => props.filters.product !== '' || props.filters.type !== null,
);

/**
 * Filtering is a server round trip: the ledger can be arbitrarily long, so the
 * client only ever holds the page it is showing.
 */
function applyFilters(overrides: { product?: string; type?: string }) {
    const query: Record<string, string> = {
        product: props.filters.product,
        type: props.filters.type ?? '',
        ...overrides,
    };

    router.get(
        index().url,
        Object.fromEntries(
            Object.entries(query).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

/** `datetime-local` wants local wall-clock time, not the UTC ISO string. */
function nowLocal(): string {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60_000)
        .toISOString()
        .slice(0, 16);
}

const defaultType = props.types[0]?.value ?? 'entrada';

const productId = ref('');
const movementType = ref(defaultType);
const quantity = ref('1');
const reason = ref('');
const occurredAt = ref(nowLocal());

/**
 * The form is controlled rather than reset natively, so `occurred_at` always
 * comes back pre-filled with the current time after a successful submit.
 */
function resetForm() {
    productId.value = '';
    movementType.value = defaultType;
    quantity.value = '1';
    reason.value = '';
    occurredAt.value = nowLocal();
}
</script>

<template>
    <Head title="Movimientos de stock" />

    <div class="flex flex-col gap-6 p-4">
        <header>
            <h1 class="text-2xl font-semibold tracking-tight">
                Movimientos de stock
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                El stock de cada producto es la suma de estos registros: no se
                edita a mano ni se corrige, se agrega un movimiento nuevo.
            </p>
        </header>

        <!--
            The form sits in a full-width card at the top rather than in a right
            column: the ledger below has six columns and needs the whole width,
            while these six fields fit in two compact rows across the card.
        -->
        <section class="rounded-xl border border-border bg-card">
            <header class="border-b border-border px-5 py-4">
                <h2 class="font-semibold tracking-tight">
                    Registrar movimiento
                </h2>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Una entrada suma unidades, una salida las resta. El motivo
                    queda asentado en el registro.
                </p>
            </header>

            <Form
                v-bind="StockMovementController.store.form()"
                :options="{ preserveScroll: true }"
                class="px-5 py-5"
                v-slot="{ errors, processing }"
                @success="resetForm"
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="grid gap-2 lg:col-span-2">
                        <Label for="movement-product">Producto</Label>
                        <select
                            id="movement-product"
                            v-model="productId"
                            name="product_id"
                            required
                            :class="controlClass"
                        >
                            <option value="" disabled>Elegí un producto</option>
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="String(product.id)"
                            >
                                {{ product.name }} · {{ product.sku }}
                            </option>
                        </select>
                        <InputError :message="errors.product_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="movement-type">Tipo</Label>
                        <select
                            id="movement-type"
                            v-model="movementType"
                            name="type"
                            required
                            :class="controlClass"
                        >
                            <option
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="movement-quantity">Cantidad</Label>
                        <Input
                            id="movement-quantity"
                            v-model="quantity"
                            name="quantity"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="100000"
                            step="1"
                            required
                            class="tabular-nums"
                        />
                        <InputError :message="errors.quantity" />
                    </div>

                    <div class="grid gap-2 lg:col-span-2">
                        <Label for="movement-occurred-at">Fecha</Label>
                        <Input
                            id="movement-occurred-at"
                            v-model="occurredAt"
                            name="occurred_at"
                            type="datetime-local"
                            required
                        />
                        <InputError :message="errors.occurred_at" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2 lg:col-span-5">
                        <Label for="movement-reason">Motivo</Label>
                        <Input
                            id="movement-reason"
                            v-model="reason"
                            name="reason"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="Compra a proveedor, venta mostrador, ajuste de inventario…"
                        />
                        <InputError :message="errors.reason" />
                    </div>

                    <div
                        class="sm:col-span-2 lg:col-span-1 lg:self-start lg:pt-6"
                    >
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                        >
                            <PackagePlus class="size-4" aria-hidden="true" />
                            Registrar
                        </Button>
                    </div>
                </div>
            </Form>
        </section>

        <section class="rounded-xl border border-border bg-card">
            <header
                class="flex flex-col gap-4 border-b border-border px-5 py-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <h2 class="font-semibold tracking-tight">Registro</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        <span class="font-mono tabular-nums">{{
                            formatNumber(movements.total)
                        }}</span>
                        movimiento{{ movements.total === 1 ? '' : 's' }} en el
                        historial
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="grid gap-2 sm:w-64">
                        <Label for="filter-product">Filtrar por producto</Label>
                        <select
                            id="filter-product"
                            :value="filters.product"
                            :class="controlClass"
                            @change="
                                applyFilters({
                                    product: (
                                        $event.target as HTMLSelectElement
                                    ).value,
                                })
                            "
                        >
                            <option value="">Todos los productos</option>
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="product.slug"
                            >
                                {{ product.name }} · {{ product.sku }}
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-2 sm:w-52">
                        <Label for="filter-type">Filtrar por tipo</Label>
                        <select
                            id="filter-type"
                            :value="filters.type ?? ''"
                            :class="controlClass"
                            @change="
                                applyFilters({
                                    type: ($event.target as HTMLSelectElement)
                                        .value,
                                })
                            "
                        >
                            <option value="">Entradas y salidas</option>
                            <option
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <Link
                        v-if="hasFilters"
                        :href="index()"
                        preserve-scroll
                        class="inline-flex h-9 items-center rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Limpiar
                    </Link>
                </div>
            </header>

            <div v-if="movements.data.length > 0" class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <caption class="sr-only">
                        Historial de entradas y salidas de inventario
                    </caption>
                    <thead class="border-b border-border">
                        <tr
                            class="font-mono text-[11px] tracking-[0.15em] text-muted-foreground uppercase"
                        >
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Fecha
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Producto
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Tipo
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-right font-medium"
                            >
                                Cantidad
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Motivo
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Usuario
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="movement in movements.data"
                            :key="movement.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <td
                                class="px-5 py-3 font-mono text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDateTime(movement.occurred_at) }}
                            </td>

                            <th
                                scope="row"
                                class="max-w-xs px-5 py-3 text-left"
                            >
                                <span
                                    class="block truncate font-medium text-foreground"
                                >
                                    {{
                                        movement.product?.name ??
                                        'Producto eliminado'
                                    }}
                                </span>
                                <span
                                    v-if="movement.product"
                                    class="mt-0.5 block font-mono text-xs font-normal text-muted-foreground"
                                >
                                    {{ movement.product.sku }}
                                </span>
                            </th>

                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        movement.type === 'entrada'
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-400/30'
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-300 dark:ring-red-400/30'
                                    "
                                >
                                    <span
                                        class="font-mono"
                                        aria-hidden="true"
                                        >{{
                                            movement.type === 'entrada'
                                                ? '+'
                                                : '−'
                                        }}</span
                                    >
                                    {{
                                        typeLabels[movement.type] ??
                                        movement.type
                                    }}
                                </span>
                            </td>

                            <td
                                class="px-5 py-3 text-right font-mono font-medium tabular-nums"
                                :class="
                                    movement.type === 'entrada'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{ movement.type === 'entrada' ? '+' : '−'
                                }}{{ formatNumber(movement.quantity) }}
                            </td>

                            <td class="max-w-sm px-5 py-3">
                                <p class="line-clamp-1">
                                    {{ movement.reason }}
                                </p>
                            </td>

                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ movement.user?.name ?? 'Sistema' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="px-5 py-20 text-center">
                <p class="text-base font-medium">
                    {{
                        hasFilters
                            ? 'Ningún movimiento coincide con el filtro'
                            : 'Todavía no hay movimientos registrados'
                    }}
                </p>
                <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        hasFilters
                            ? 'Probá con otro producto o quitá el filtro de tipo.'
                            : 'Registrá la primera entrada con el formulario de arriba para que el stock empiece a contarse.'
                    }}
                </p>
                <Link
                    v-if="hasFilters"
                    :href="index()"
                    class="mt-5 inline-flex h-9 items-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Ver todo el registro
                </Link>
            </div>

            <div
                v-if="movements.data.length > 0"
                class="border-t border-border px-5 py-4"
            >
                <Pagination
                    :links="movements.links"
                    :from="movements.from"
                    :to="movements.to"
                    :total="movements.total"
                />
            </div>
        </section>
    </div>
</template>
