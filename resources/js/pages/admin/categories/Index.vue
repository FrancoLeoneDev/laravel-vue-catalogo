<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatNumber } from '@/lib/format';
import { create, edit, index } from '@/routes/admin/categories';
import type { Category, Paginated } from '@/types/catalog';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categorías', href: index() }],
    },
});

defineProps<{
    categories: Paginated<Category>;
}>();

/**
 * The server refuses to delete a category that still has products, so the
 * dialog says so before the operator clicks rather than after.
 */
function productCount(category: Category): number {
    return category.products_count ?? 0;
}
</script>

<template>
    <Head title="Categorías" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Categorías
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Los rubros con los que se agrupa el catálogo. Cada producto
                    pertenece a una sola categoría.
                </p>
            </div>

            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" aria-hidden="true" />
                    Nueva categoría
                </Link>
            </Button>
        </header>

        <section
            v-if="categories.data.length > 0"
            class="rounded-xl border border-border bg-card"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <caption class="sr-only">
                        Listado de categorías del catálogo
                    </caption>
                    <thead class="border-b border-border">
                        <tr
                            class="font-mono text-[11px] tracking-[0.15em] text-muted-foreground uppercase"
                        >
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Nombre
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Slug
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left font-medium"
                            >
                                Descripción
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-right font-medium"
                            >
                                Productos
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-right font-medium"
                            >
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <th
                                scope="row"
                                class="px-5 py-3 text-left font-medium text-foreground"
                            >
                                {{ category.name }}
                            </th>
                            <td
                                class="px-5 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ category.slug }}
                            </td>
                            <td
                                class="max-w-xs px-5 py-3 text-muted-foreground"
                            >
                                <p
                                    v-if="category.description"
                                    class="line-clamp-1"
                                >
                                    {{ category.description }}
                                </p>
                                <span v-else class="text-muted-foreground/60"
                                    >—</span
                                >
                            </td>
                            <td
                                class="px-5 py-3 text-right font-mono tabular-nums"
                            >
                                {{ formatNumber(productCount(category)) }}
                            </td>
                            <td class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link :href="edit(category.slug)">
                                            <Pencil
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            Editar
                                        </Link>
                                    </Button>

                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <Trash2
                                                    class="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                Eliminar
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <Form
                                                v-bind="
                                                    CategoryController.destroy.form(
                                                        category.slug,
                                                    )
                                                "
                                                :options="{
                                                    preserveScroll: true,
                                                }"
                                                class="space-y-6"
                                                v-slot="{ processing }"
                                            >
                                                <DialogHeader class="space-y-3">
                                                    <DialogTitle>
                                                        ¿Eliminar la categoría
                                                        «{{ category.name }}»?
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        La acción no se puede
                                                        deshacer. La categoría
                                                        deja de estar disponible
                                                        como filtro del
                                                        catálogo.
                                                    </DialogDescription>
                                                </DialogHeader>

                                                <div
                                                    v-if="
                                                        productCount(category) >
                                                        0
                                                    "
                                                    class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-400/20 dark:bg-red-950/40 dark:text-red-300"
                                                >
                                                    <p class="font-medium">
                                                        No se puede eliminar
                                                        todavía
                                                    </p>
                                                    <p class="mt-1">
                                                        Tiene
                                                        <span
                                                            class="font-mono tabular-nums"
                                                            >{{
                                                                formatNumber(
                                                                    productCount(
                                                                        category,
                                                                    ),
                                                                )
                                                            }}</span
                                                        >
                                                        producto{{
                                                            productCount(
                                                                category,
                                                            ) === 1
                                                                ? ''
                                                                : 's'
                                                        }}
                                                        asociado{{
                                                            productCount(
                                                                category,
                                                            ) === 1
                                                                ? ''
                                                                : 's'
                                                        }}. Reasignalos a otra
                                                        categoría antes de
                                                        eliminarla.
                                                    </p>
                                                </div>

                                                <DialogFooter class="gap-2">
                                                    <DialogClose as-child>
                                                        <Button
                                                            type="button"
                                                            variant="secondary"
                                                        >
                                                            Cancelar
                                                        </Button>
                                                    </DialogClose>

                                                    <Button
                                                        type="submit"
                                                        variant="destructive"
                                                        :disabled="
                                                            processing ||
                                                            productCount(
                                                                category,
                                                            ) > 0
                                                        "
                                                    >
                                                        Eliminar categoría
                                                    </Button>
                                                </DialogFooter>
                                            </Form>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border px-5 py-4">
                <Pagination
                    :links="categories.links"
                    :from="categories.from"
                    :to="categories.to"
                    :total="categories.total"
                />
            </div>
        </section>

        <div
            v-else
            class="rounded-xl border border-dashed border-border py-20 text-center"
        >
            <p class="text-base font-medium">Todavía no hay categorías</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                Creá la primera para poder clasificar los productos del
                catálogo.
            </p>
            <Button class="mt-5" as-child>
                <Link :href="create()">
                    <Plus class="size-4" aria-hidden="true" />
                    Nueva categoría
                </Link>
            </Button>
        </div>
    </div>
</template>
