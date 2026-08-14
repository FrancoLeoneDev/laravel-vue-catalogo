<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProductCard from '@/components/ProductCard.vue';
import StockBadge from '@/components/StockBadge.vue';
import { formatPrice } from '@/lib/format';
import { home } from '@/routes';
import type { Product } from '@/types/catalog';

const props = defineProps<{
    product: Product;
    currentStock: number;
    related: Product[];
}>();

const categoryHref = computed(
    () => home({ query: { category: props.product.category?.slug } }).url,
);
</script>

<template>
    <Head :title="product.name" />

    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <nav
            aria-label="Migas de pan"
            class="mb-8 text-sm text-muted-foreground"
        >
            <ol class="flex flex-wrap items-center gap-1.5">
                <li>
                    <Link :href="home().url" class="hover:text-foreground"
                        >Catálogo</Link
                    >
                </li>
                <li aria-hidden="true">/</li>
                <li>
                    <Link :href="categoryHref" class="hover:text-foreground">{{
                        product.category?.name
                    }}</Link>
                </li>
                <li aria-hidden="true">/</li>
                <li class="text-foreground">{{ product.name }}</li>
            </ol>
        </nav>

        <div class="grid gap-10 lg:grid-cols-[1.15fr_1fr] lg:gap-16">
            <div
                class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted/40"
            >
                <img
                    v-if="product.image_path"
                    :src="`/storage/${product.image_path}`"
                    :alt="product.name"
                    class="size-full object-cover"
                />
                <div
                    v-else
                    class="flex flex-col items-center gap-3 text-muted-foreground"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.25"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-14"
                        aria-hidden="true"
                    >
                        <path d="M3 7l9-4 9 4-9 4-9-4Z" />
                        <path d="M3 12l9 4 9-4" />
                        <path d="M3 17l9 4 9-4" />
                    </svg>
                    <span class="font-mono text-xs tracking-wider uppercase"
                        >Sin imagen</span
                    >
                </div>
            </div>

            <div>
                <p
                    class="font-mono text-xs tracking-[0.2em] text-muted-foreground uppercase"
                >
                    {{ product.category?.name }}
                </p>
                <h1
                    class="mt-3 text-3xl leading-tight font-semibold tracking-tight text-balance"
                >
                    {{ product.name }}
                </h1>

                <p
                    class="mt-5 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ formatPrice(product.price) }}
                </p>

                <div class="mt-5">
                    <StockBadge
                        :stock="currentStock"
                        :threshold="product.low_stock_threshold"
                    />
                </div>

                <p
                    v-if="product.description"
                    class="mt-7 leading-relaxed text-muted-foreground"
                >
                    {{ product.description }}
                </p>

                <dl
                    class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-border bg-border"
                >
                    <div class="bg-card p-4">
                        <dt
                            class="font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                        >
                            SKU
                        </dt>
                        <dd class="mt-1 font-mono text-sm">
                            {{ product.sku }}
                        </dd>
                    </div>
                    <div class="bg-card p-4">
                        <dt
                            class="font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                        >
                            Rubro
                        </dt>
                        <dd class="mt-1 text-sm">
                            {{ product.category?.name }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-6 text-xs text-muted-foreground">
                    El stock mostrado se calcula sumando los movimientos de
                    inventario registrados para este producto.
                </p>
            </div>
        </div>

        <section v-if="related.length > 0" class="mt-16">
            <h2 class="text-lg font-semibold tracking-tight">
                Otros productos del rubro
            </h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard
                    v-for="item in related"
                    :key="item.id"
                    :product="item"
                />
            </div>
        </section>
    </div>
</template>
