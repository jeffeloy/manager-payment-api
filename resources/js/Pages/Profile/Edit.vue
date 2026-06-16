<script setup lang="ts">
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profile" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <!-- Header -->
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <Button
                        as-child
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-slate-500"
                    >
                        <Link :href="route('dashboard')">
                            <ArrowLeft class="h-5 w-5" />
                            <span class="sr-only">Back to dashboard</span>
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-lg font-semibold tracking-tight text-slate-900">
                            Profile
                        </h1>
                        <p class="text-xs text-slate-500">
                            Manage your account settings and preferences
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </main>
    </div>
</template>
