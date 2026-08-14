<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { home, login } from '@/routes';
import { dashboard } from '@/routes/admin';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="sticky top-0 z-40 border-b border-border/80 bg-background/85 backdrop-blur supports-[backdrop-filter]:bg-background/70"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="home()"
                    class="group flex items-center gap-2.5 rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                        aria-hidden="true"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-4"
                        >
                            <path d="M3 7l9-4 9 4-9 4-9-4Z" />
                            <path d="M3 12l9 4 9-4" />
                            <path d="M3 17l9 4 9-4" />
                        </svg>
                    </span>
                    <span class="flex flex-col leading-none">
                        <span class="text-sm font-semibold tracking-tight"
                            >Ferretería Sur</span
                        >
                        <span
                            class="font-mono text-[10px] tracking-widest text-muted-foreground uppercase"
                            >Catálogo</span
                        >
                    </span>
                </Link>

                <div class="ml-auto flex items-center gap-2">
                    <AppearanceTabs class="hidden sm:flex" />

                    <Link
                        v-if="user"
                        :href="dashboard()"
                        class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        Panel
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="inline-flex h-9 items-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        Ingresar al panel
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/80">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <p>
                    &copy; {{ year }} Ferretería Sur — catálogo de demostración.
                </p>
                <p class="font-mono text-xs">
                    Laravel · Inertia · Vue 3 · MySQL · Tailwind
                </p>
            </div>
        </footer>
    </div>
</template>
