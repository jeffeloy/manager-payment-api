<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Wallet, Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Confirm Password" />

    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
                    <Wallet class="h-6 w-6" />
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Secure Area</h1>
                <p class="mt-1 text-sm text-slate-500">Please confirm your password before continuing.</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            autofocus
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="text-sm font-medium text-rose-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            class="w-full"
                            :class="{ 'opacity-70': form.processing }"
                            :disabled="form.processing"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Confirm
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
