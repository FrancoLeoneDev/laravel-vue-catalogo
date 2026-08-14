<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import type { PaginationLink } from '@/types/catalog';

const props = defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();

/** Drop Laravel's "&laquo; Previous" / "Next &raquo;" markup for clean labels. */
const pageLinks = computed(() =>
    props.links.map((link, index) => ({
        ...link,
        display:
            index === 0
                ? 'Anterior'
                : index === props.links.length - 1
                  ? 'Siguiente'
                  : link.label,
        isEdge: index === 0 || index === props.links.length - 1,
    })),
);
</script>

<template>
    <nav
        v-if="total > 0"
        class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        aria-label="Paginación"
    >
        <p class="text-sm text-muted-foreground">
            Mostrando
            <span class="font-medium text-foreground">{{
                formatNumber(from ?? 0)
            }}</span
            >–<span class="font-medium text-foreground">{{
                formatNumber(to ?? 0)
            }}</span>
            de
            <span class="font-medium text-foreground">{{
                formatNumber(total)
            }}</span>
        </p>

        <ul v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
            <li v-for="link in pageLinks" :key="link.label">
                <span
                    v-if="link.url === null"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground/50"
                    :class="{ 'hidden sm:inline-flex': link.isEdge }"
                    >{{ link.display }}</span
                >
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors"
                    :class="[
                        link.active
                            ? 'bg-primary font-medium text-primary-foreground'
                            : 'text-foreground hover:bg-accent',
                        link.isEdge ? 'hidden sm:inline-flex' : '',
                    ]"
                    >{{ link.display }}</Link
                >
            </li>
        </ul>
    </nav>
</template>
