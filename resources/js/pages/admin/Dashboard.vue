<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StockBadge from '@/components/StockBadge.vue';
import { formatDateTime, formatNumber } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import movements from '@/routes/admin/movements';
import products from '@/routes/admin/products';
import type { Product, StockMovement } from '@/types/catalog';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Panel', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        products: number;
        activeProducts: number;
        categories: number;
        unitsInStock: number;
        lowStockCount: number;
        movementsLast30Days: number;
    };
    lowStock: Product[];
    recentMovements: StockMovement[];
}>();

const cards = [
    {
        label: 'Productos',
        value: formatNumber(props.stats.products),
        detail: `${formatNumber(props.stats.activeProducts)} activos`,
    },
    {
        label: 'Categorías',
        value: formatNumber(props.stats.categories),
        detail: 'rubros del catálogo',
    },
    {
        label: 'Unidades en stock',
        value: formatNumber(props.stats.unitsInStock),
        detail: 'suma de todos los movimientos',
    },
    {
        label: 'Movimientos (30 días)',
        value: formatNumber(props.stats.movementsLast30Days),
        detail: 'entradas y salidas registradas',
    },
];
</script>

<template>
    <Head title="Panel" />

    <div class="flex flex-col gap-6 p-4">
        <header>
            <h1 class="text-2xl font-semibold tracking-tight">Panel</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                El stock de cada producto se calcula sumando sus movimientos de
                inventario. No se edita a mano.
            </p>
        </header>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.label"
                class="rounded-xl border border-border bg-card p-5"
            >
                <dt
                    class="font-mono text-[11px] tracking-[0.15em] text-muted-foreground uppercase"
                >
                    {{ card.label }}
                </dt>
                <dd
                    class="mt-2 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ card.value }}
                </dd>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ card.detail }}
                </p>
            </div>
        </dl>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-border bg-card">
                <header
                    class="flex items-center justify-between gap-3 border-b border-border px-5 py-4"
                >
                    <div>
                        <h2 class="font-semibold tracking-tight">Stock bajo</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatNumber(stats.lowStockCount) }} productos en
                            o por debajo de su umbral
                        </p>
                    </div>
                    <Link
                        :href="products.index().url"
                        class="rounded-md px-2 py-1 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Ver todos
                    </Link>
                </header>

                <ul v-if="lowStock.length > 0" class="divide-y divide-border">
                    <li
                        v-for="product in lowStock"
                        :key="product.id"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="products.edit(product.slug).url"
                                class="block truncate text-sm font-medium hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {{ product.name }}
                            </Link>
                            <p
                                class="mt-0.5 font-mono text-xs text-muted-foreground"
                            >
                                {{ product.sku }} · umbral
                                {{ product.low_stock_threshold }}
                            </p>
                        </div>
                        <StockBadge
                            :stock="product.current_stock ?? 0"
                            :threshold="product.low_stock_threshold"
                        />
                    </li>
                </ul>

                <p v-else class="px-5 py-10 text-center text-sm text-muted-foreground">
                    Ningún producto llegó a su umbral de stock.
                </p>
            </section>

            <section class="rounded-xl border border-border bg-card">
                <header
                    class="flex items-center justify-between gap-3 border-b border-border px-5 py-4"
                >
                    <div>
                        <h2 class="font-semibold tracking-tight">
                            Últimos movimientos
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Registro auditable de cada cambio de inventario
                        </p>
                    </div>
                    <Link
                        :href="movements.index().url"
                        class="rounded-md px-2 py-1 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Ver todos
                    </Link>
                </header>

                <ul
                    v-if="recentMovements.length > 0"
                    class="divide-y divide-border"
                >
                    <li
                        v-for="movement in recentMovements"
                        :key="movement.id"
                        class="flex items-start justify-between gap-4 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ movement.product?.name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                {{ movement.reason }} ·
                                {{ formatDateTime(movement.occurred_at) }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 font-mono text-sm font-medium tabular-nums"
                            :class="
                                movement.type === 'entrada'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            {{ movement.type === 'entrada' ? '+' : '−'
                            }}{{ formatNumber(movement.quantity) }}
                        </span>
                    </li>
                </ul>

                <p v-else class="px-5 py-10 text-center text-sm text-muted-foreground">
                    Todavía no hay movimientos registrados.
                </p>
            </section>
        </div>
    </div>
</template>
