<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categorías', href: index() },
            { title: 'Nueva categoría', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva categoría" />

    <div class="flex flex-col gap-6 p-4">
        <header>
            <h1 class="text-2xl font-semibold tracking-tight">
                Nueva categoría
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Registrá un rubro nuevo para agrupar productos del catálogo.
            </p>
        </header>

        <section class="max-w-2xl rounded-xl border border-border bg-card p-5">
            <Form
                v-bind="CategoryController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        name="name"
                        type="text"
                        required
                        autocomplete="off"
                        autofocus
                        maxlength="255"
                        placeholder="Herramientas manuales"
                    />
                    <p class="text-xs text-muted-foreground">
                        La dirección web de la categoría se genera
                        automáticamente a partir del nombre.
                    </p>
                    <InputError :message="errors.name" />
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Descripción</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="1000"
                        placeholder="Opcional: en qué se usa este rubro, qué incluye y qué no."
                        class="w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                    />
                    <p class="text-xs text-muted-foreground">
                        Hasta 1000 caracteres.
                    </p>
                    <InputError :message="errors.description" />
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        Crear categoría
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </section>
    </div>
</template>
