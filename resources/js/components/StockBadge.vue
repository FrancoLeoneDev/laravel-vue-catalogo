<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

const props = withDefaults(
    defineProps<{
        stock: number;
        threshold?: number;
        showUnits?: boolean;
    }>(),
    { threshold: 0, showUnits: true },
);

const level = computed(() => {
    if (props.stock <= 0) {
        return 'out';
    }

    if (props.stock <= props.threshold) {
        return 'low';
    }

    return 'ok';
});

const classes = computed(
    () =>
        ({
            out: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-300 dark:ring-red-400/30',
            low: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-400/30',
            ok: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-400/30',
        })[level.value],
);

const label = computed(() => {
    if (level.value === 'out') {
        return 'Sin stock';
    }

    const units = `${formatNumber(props.stock)} u.`;

    return props.showUnits
        ? units
        : level.value === 'low'
          ? 'Stock bajo'
          : 'En stock';
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
        :class="classes"
    >
        <span
            class="size-1.5 rounded-full"
            :class="{
                'bg-red-500': level === 'out',
                'bg-amber-500': level === 'low',
                'bg-emerald-500': level === 'ok',
            }"
        />
        {{ label }}
    </span>
</template>
