<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Info } from '@lucide/vue';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/products';
import type { Category } from '@/types/catalog';

defineProps<{
    categories: Category[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Productos',
                href: index(),
            },
            {
                title: 'Nuevo producto',
                href: create(),
            },
        ],
    },
});

const fieldClass =
    'w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <Head title="Nuevo producto" />

    <div class="mx-auto w-full max-w-3xl p-4 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Nuevo producto
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Cargá los datos del producto. El slug se genera solo a partir
                del nombre.
            </p>
        </div>

        <!-- The project's key decision, stated where it matters. -->
        <div
            class="mt-6 flex gap-3 rounded-lg border border-border bg-muted/40 p-4"
        >
            <Info
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <div class="space-y-1">
                <p class="text-sm font-medium">El stock arranca en 0</p>
                <p class="text-sm text-muted-foreground">
                    El stock no se carga acá. Se calcula a partir de los
                    movimientos de inventario que registres. Después de crear el
                    producto vas a poder registrar la entrada inicial.
                </p>
            </div>
        </div>

        <Form
            v-bind="ProductController.store.form()"
            enctype="multipart/form-data"
            class="mt-8 space-y-6"
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
                    <option value="" disabled selected>
                        Elegí una categoría
                    </option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
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
                    placeholder="Amoladora angular 4½″ 900 W"
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
                    placeholder="Detalle técnico, materiales, medidas…"
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
                        placeholder="0.00"
                    />
                    <p class="text-xs text-muted-foreground">
                        En pesos, con hasta dos decimales.
                    </p>
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
                        placeholder="AMO-900-45"
                    />
                    <p class="text-xs text-muted-foreground">
                        Se guarda en mayúsculas. Tiene que ser único.
                    </p>
                    <InputError :message="errors.sku" />
                </div>
            </div>

            <div class="grid gap-2 sm:max-w-xs">
                <Label for="low_stock_threshold">Umbral de stock bajo</Label>
                <Input
                    id="low_stock_threshold"
                    name="low_stock_threshold"
                    type="number"
                    step="1"
                    min="0"
                    max="100000"
                    required
                    inputmode="numeric"
                    :default-value="10"
                    class="font-mono"
                />
                <p class="text-xs text-muted-foreground">
                    Cuando el stock calculado baje hasta este número, el
                    producto se marca en alerta.
                </p>
                <InputError :message="errors.low_stock_threshold" />
            </div>

            <div class="grid gap-2">
                <Label for="image">Imagen</Label>
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
                    JPG, PNG o WEBP. Hasta 2 MB.
                </p>
                <InputError :message="errors.image" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        name="is_active"
                        value="1"
                        :default-value="true"
                    />
                    <Label for="is_active"
                        >Producto activo en el catálogo</Label
                    >
                </div>
                <p class="text-xs text-muted-foreground">
                    Los productos inactivos no se muestran en el catálogo
                    público.
                </p>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex items-center gap-3 border-t border-border pt-6">
                <Button type="submit" :disabled="processing">
                    Crear producto
                </Button>

                <Link
                    :href="index().url"
                    class="inline-flex h-9 items-center rounded-md px-4 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Cancelar
                </Link>
            </div>
        </Form>
    </div>
</template>
