<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    PanelLeftClose,
    PanelLeftOpen,
    Wallet,
} from 'lucide-vue-next';

const collapsed = ref(true);
</script>

<template>
    <aside
        :class="[
            'flex flex-col border-r border-slate-200 bg-white transition-all duration-300 ease-in-out',
            collapsed ? 'w-20' : 'w-64',
        ]"
    >
        <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white"
            >
                <Wallet class="h-5 w-5" />
            </div>
            <span v-if="!collapsed" class="truncate text-lg font-semibold tracking-tight">
                PayFlow
            </span>
        </div>

        <nav class="flex flex-1 flex-col gap-1 p-3">
            <Link
                :href="route('dashboard')"
                :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                    route().current('dashboard')
                        ? 'bg-slate-900 text-white'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
            >
                <LayoutDashboard class="h-5 w-5 shrink-0" />
                <span v-if="!collapsed">Dashboard</span>
            </Link>
        </nav>

        <div class="border-t border-slate-100 p-3">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                @click="collapsed = !collapsed"
            >
                <PanelLeftClose v-if="!collapsed" class="h-5 w-5 shrink-0" />
                <PanelLeftOpen v-else class="h-5 w-5 shrink-0" />
                <span v-if="!collapsed">Collapse</span>
            </button>
        </div>
    </aside>
</template>
