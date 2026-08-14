<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import ProductCard from '@/components/ProductCard.vue';
import { formatNumber } from '@/lib/format';
import { home } from '@/routes';
import type { Category, Paginated, Product } from '@/types/catalog';

const props = defineProps<{
    products: Paginated<Product>;
    categories: Category[];
    filters: { search: string; category: string; sort: string };
}>();

const search = ref(props.filters.search);

const sortOptions = [
    { value: 'name', label: 'Nombre (A–Z)' },
    { value: 'price_asc', label: 'Precio: menor a mayor' },
    { value: 'price_desc', label: 'Precio: mayor a menor' },
    { value: 'newest', label: 'Más recientes' },
];

/**
 * Every filter change is a server round trip: the client never holds the full
 * catalog, only the page it is showing.
 */
function applyFilters(overrides: Record<string, string>) {
    const query = {
        search: search.value,
        category: props.filters.category,
        sort: props.filters.sort,
        ...overrides,
    };

    router.get(
        home().url,
        Object.fromEntries(
            Object.entries(query).filter(
                ([, value]) => value !== '' && value !== 'name',
            ),
        ),
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let debounce: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters({}), 300);
});
</script>

<template>
    <Head title="Catálogo" />

    <!-- Search-first hero: the page opens with the tool, not with copy about it. -->
    <section class="border-b border-border/80 bg-muted/30">
        <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <p
                class="font-mono text-xs tracking-[0.2em] text-muted-foreground uppercase"
            >
                Catálogo · {{ formatNumber(products.total) }} productos
            </p>
            <h1
                class="mt-3 max-w-2xl text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-4xl"
            >
                Herramientas, materiales y repuestos con stock al día.
            </h1>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <svg
                        class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <label for="catalog-search" class="sr-only"
                        >Buscar productos</label
                    >
                    <input
                        id="catalog-search"
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre o SKU…"
                        class="h-12 w-full rounded-md border border-border bg-background pr-4 pl-11 text-base placeholder:text-muted-foreground focus-visible:border-foreground focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none"
                    />
                </div>

                <div class="sm:w-56">
                    <label for="catalog-sort" class="sr-only"
                        >Ordenar por</label
                    >
                    <select
                        id="catalog-sort"
                        :value="filters.sort"
                        class="h-12 w-full rounded-md border border-border bg-background px-3 text-sm focus-visible:border-foreground focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none"
                        @change="
                            applyFilters({
                                sort: ($event.target as HTMLSelectElement)
                                    .value,
                            })
                        "
                    >
                        <option
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Category filter as links, so every view is a shareable URL. -->
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <Link
                    :href="
                        home({ query: { search: filters.search || undefined } })
                            .url
                    "
                    preserve-scroll
                    class="inline-flex h-8 items-center rounded-full border px-3.5 text-sm transition-colors"
                    :class="
                        filters.category === ''
                            ? 'border-foreground bg-foreground text-background'
                            : 'border-border hover:bg-accent'
                    "
                >
                    Todos
                </Link>
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="
                        home({
                            query: {
                                search: filters.search || undefined,
                                category: category.slug,
                            },
                        }).url
                    "
                    preserve-scroll
                    class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3.5 text-sm transition-colors"
                    :class="
                        filters.category === category.slug
                            ? 'border-foreground bg-foreground text-background'
                            : 'border-border hover:bg-accent'
                    "
                >
                    {{ category.name }}
                    <span class="font-mono text-[11px] opacity-60">{{
                        category.products_count
                    }}</span>
                </Link>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
        <div
            v-if="products.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <ProductCard
                v-for="product in products.data"
                :key="product.id"
                :product="product"
            />
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-border py-20 text-center"
        >
            <p class="text-base font-medium">No encontramos productos</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                Probá con otro término de búsqueda o quitá el filtro de
                categoría.
            </p>
            <Link
                :href="home().url"
                class="mt-5 inline-flex h-9 items-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-accent"
            >
                Ver todo el catálogo
            </Link>
        </div>

        <div v-if="products.data.length > 0" class="mt-10">
            <Pagination
                :links="products.links"
                :from="products.from"
                :to="products.to"
                :total="products.total"
            />
        </div>
    </section>
</template>
