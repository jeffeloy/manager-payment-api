<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppSidebar from '@/components/app/AppSidebar.vue';
import UserMenu from '@/components/app/UserMenu.vue';
import FlashAlert from '@/components/app/FlashAlert.vue';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <AppSidebar />

        <div class="flex flex-1 flex-col">
            <header
                class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6"
            >
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="subtitle" class="text-xs text-slate-500">{{ subtitle }}</p>
                </div>
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <UserMenu />
                </div>
            </header>

            <main class="flex-1 space-y-6 p-6">
                <FlashAlert />
                <slot />
            </main>
        </div>
    </div>
</template>
