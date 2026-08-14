<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Ingresá al panel',
        description: 'Gestión de productos, categorías y movimientos de stock',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// This is a portfolio demo: the credentials are published on purpose and the
// form ships pre-filled so the panel is reachable in a single click.
const demo = {
    email: 'demo@demo.com',
    password: 'password',
};
</script>

<template>
    <Head title="Ingresar" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div
        class="mb-6 rounded-lg border border-emerald-600/25 bg-emerald-50 p-4 dark:bg-emerald-950/40"
    >
        <p
            class="font-mono text-[11px] tracking-[0.18em] text-emerald-800 uppercase dark:text-emerald-300"
        >
            Cuenta de demostración
        </p>
        <dl class="mt-2.5 space-y-1 text-sm">
            <div class="flex items-baseline gap-2">
                <dt class="w-20 shrink-0 text-emerald-800/70 dark:text-emerald-300/70">
                    Usuario
                </dt>
                <dd class="font-mono font-medium text-emerald-900 dark:text-emerald-100">
                    {{ demo.email }}
                </dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="w-20 shrink-0 text-emerald-800/70 dark:text-emerald-300/70">
                    Contraseña
                </dt>
                <dd class="font-mono font-medium text-emerald-900 dark:text-emerald-100">
                    {{ demo.password }}
                </dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-emerald-800/80 dark:text-emerald-300/80">
            Ya están cargadas en el formulario: tocá “Ingresar” y entrás directo.
        </p>
    </div>

    <PasskeyVerify
        label="Ingresar con passkey"
        loading-label="Autenticando…"
        separator="O continuá con tu correo"
    />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    :default-value="demo.email"
                    placeholder="correo@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Contraseña</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        ¿Olvidaste tu contraseña?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :default-value="demo.password"
                    placeholder="Contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Mantener la sesión iniciada</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Ingresar
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            ¿No tenés cuenta?
            <TextLink :href="register()" :tabindex="5">Registrate</TextLink>
        </div>
    </Form>
</template>
