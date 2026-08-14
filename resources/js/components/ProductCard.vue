<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatNumber, formatPrice } from '@/lib/format';
import { show } from '@/routes/catalog';
import type { Product } from '@/types/catalog';

const props = defineProps<{ product: Product }>();

const stock = computed(() => props.product.current_stock ?? 0);
const threshold = computed(() => props.product.low_stock_threshold);

const level = computed(() => {
    if (stock.value <= 0) return 'out';
    if (stock.value <= threshold.value) return 'low';
    return 'ok';
});

/**
 * The rail reads the derived balance against the product's own threshold:
 * full once stock is comfortably above it, thin as it runs down.
 */
const railWidth = computed(() => {
    const ceiling = Math.max(threshold.value * 3, 1);

    return `${Math.min(100, Math.round((stock.value / ceiling) * 100))}%`;
});

const railColor = computed(
    () =>
        ({
            out: 'bg-red-500',
            low: 'bg-amber-500',
            ok: 'bg-emerald-500',
        })[level.value],
);

const stockLabel = computed(() => {
    if (level.value === 'out') return 'Sin stock';
    if (level.value === 'low') return `Últimas ${formatNumber(stock.value)} u.`;

    return `${formatNumber(stock.value)} u. disponibles`;
});
</script>

<template>
    <article
        class="group relative flex flex-col overflow-hidden rounded-lg border border-border bg-card transition-colors hover:border-foreground/25"
    >
        <!-- Stock rail: the derived balance, made visible. -->
        <div class="h-1 w-full bg-muted" aria-hidden="true">
            <div
                class="h-full transition-[width] duration-500"
                :class="railColor"
                :style="{ width: railWidth }"
            />
        </div>

        <div class="flex flex-1 flex-col gap-3 p-5">
            <div class="flex items-start justify-between gap-3">
                <span
                    class="font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                >
                    {{ product.category?.name }}
                </span>
                <span class="font-mono text-[11px] text-muted-foreground">
                    {{ product.sku }}
                </span>
            </div>

            <h3 class="text-base leading-snug font-medium tracking-tight">
                <Link
                    :href="show(product.slug)"
                    class="after:absolute after:inset-0 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    {{ product.name }}
                </Link>
            </h3>

            <p
                v-if="product.description"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ product.description }}
            </p>

            <div class="mt-auto flex items-end justify-between gap-3 pt-2">
                <p class="text-lg font-semibold tracking-tight tabular-nums">
                    {{ formatPrice(product.price) }}
                </p>
                <p
                    class="font-mono text-xs"
                    :class="{
                        'text-red-600 dark:text-red-400': level === 'out',
                        'text-amber-600 dark:text-amber-400': level === 'low',
                        'text-muted-foreground': level === 'ok',
                    }"
                >
                    {{ stockLabel }}
                </p>
            </div>
        </div>
    </article>
</template>
