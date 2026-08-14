<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import Pagination from '@/components/Pagination.vue';
import StockBadge from '@/components/StockBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatNumber, formatPrice } from '@/lib/format';
import { create, edit, index } from '@/routes/admin/products';
import type { Category, Paginated, Product } from '@/types/catalog';

const props = defineProps<{
    products: Paginated<Product>;
    categories: Category[];
    filters: { search: string; category: string; status: string | null };
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

const search = ref(props.filters.search);

const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

const controlClass =
    'h-10 w-full rounded-md border border-border bg-background px-3 text-sm focus-visible:border-foreground focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none';

/**
 * Filtering is a server round trip: the client only ever holds the page it is
 * showing, never the full catalog.
 */
function applyFilters(overrides: Record<string, string | null>) {
    const query = {
        search: search.value,
        category: props.filters.category,
        status: props.filters.status,
        ...overrides,
    };

    router.get(
        index().url,
        Object.fromEntries(
            Object.entries(query).filter(
                ([, value]) => value !== '' && value !== null,
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

/** The product queued for deletion; non-null keeps the confirm dialog open. */
const productToDelete = ref<Product | null>(null);

function closeDeleteDialog(open: boolean) {
    if (!open) {
        productToDelete.value = null;
    }
}
</script>

<template>
    <Head title="Productos" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Productos</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ formatNumber(products.total) }} productos en el catálogo.
                    El stock se calcula con los movimientos de inventario.
                </p>
            </div>

            <Button as-child>
                <Link
                    :href="create()"
                    class="focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Nuevo producto
                </Link>
            </Button>
        </div>

        <!-- Filter bar: every control is a server round trip. -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_11rem]">
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <label for="products-search" class="sr-only">
                    Buscar productos
                </label>
                <input
                    id="products-search"
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre o SKU…"
                    :class="controlClass"
                    class="pl-9"
                />
            </div>

            <div>
                <label for="products-category" class="sr-only">
                    Filtrar por categoría
                </label>
                <select
                    id="products-category"
                    :value="filters.category"
                    :class="controlClass"
                    @change="
                        applyFilters({
                            category: ($event.target as HTMLSelectElement)
                                .value,
                        })
                    "
                >
                    <option value="">Todas las categorías</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.slug"
                    >
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <div>
                <label for="products-status" class="sr-only">
                    Filtrar por estado
                </label>
                <select
                    id="products-status"
                    :value="filters.status ?? ''"
                    :class="controlClass"
                    @change="
                        applyFilters({
                            status: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>

        <div
            v-if="products.data.length > 0"
            class="overflow-hidden rounded-lg border border-border bg-card"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-b border-border bg-muted/40 text-left"
                        >
                            <th
                                scope="col"
                                class="px-4 py-3 font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Producto
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Categoría
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Precio
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Stock
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Estado
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-mono text-[11px] tracking-wider text-muted-foreground uppercase"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="border-b border-border transition-colors last:border-b-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-foreground">
                                    {{ product.name }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-muted-foreground"
                                >
                                    {{ product.sku }}
                                </p>
                            </td>

                            <td class="px-4 py-3 text-muted-foreground">
                                {{ product.category?.name ?? '—' }}
                            </td>

                            <td
                                class="px-4 py-3 text-right font-medium tabular-nums"
                            >
                                {{ formatPrice(product.price) }}
                            </td>

                            <td class="px-4 py-3">
                                <StockBadge
                                    :stock="product.current_stock ?? 0"
                                    :threshold="product.low_stock_threshold"
                                />
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        product.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-400/30'
                                            : 'bg-muted text-muted-foreground ring-border'
                                    "
                                >
                                    {{
                                        product.is_active
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <Button as-child variant="ghost" size="sm">
                                        <Link
                                            :href="edit(product.slug)"
                                            class="focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <Pencil
                                                class="size-4"
                                                aria-hidden="true"
                                            />
                                            Editar
                                        </Link>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-red-600 hover:bg-red-50 hover:text-red-700 focus-visible:ring-2 focus-visible:ring-ring dark:text-red-400 dark:hover:bg-red-950/50 dark:hover:text-red-300"
                                        :aria-label="`Eliminar ${product.name}`"
                                        @click="productToDelete = product"
                                    >
                                        <Trash2
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-border py-20 text-center"
        >
            <p class="text-base font-medium">No hay productos que coincidan</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                Probá con otro término de búsqueda o quitá los filtros de
                categoría y estado.
            </p>
            <Link
                :href="index().url"
                class="mt-5 inline-flex h-9 items-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                Limpiar filtros
            </Link>
        </div>

        <Pagination
            v-if="products.data.length > 0"
            :links="products.links"
            :from="products.from"
            :to="products.to"
            :total="products.total"
        />
    </div>

    <!-- Deleting takes the product's movement ledger with it, so it is confirmed. -->
    <Dialog :open="productToDelete !== null" @update:open="closeDeleteDialog">
        <DialogContent>
            <template v-if="productToDelete">
                <Form
                    v-bind="
                        ProductController.destroy.form(productToDelete.slug)
                    "
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ processing }"
                    @success="productToDelete = null"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>
                            ¿Eliminar «{{ productToDelete.name }}»?
                        </DialogTitle>
                        <DialogDescription>
                            Se borra el producto y todos sus movimientos de
                            inventario. Esta acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Eliminar producto
                        </Button>
                    </DialogFooter>
                </Form>
            </template>
        </DialogContent>
    </Dialog>
</template>
