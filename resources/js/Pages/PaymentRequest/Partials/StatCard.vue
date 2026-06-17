<script setup lang="ts">
import { computed, type Component } from 'vue';

type Accent = 'amber' | 'emerald' | 'slate' | 'red' | 'zync';

const props = defineProps<{
    label: string;
    value: string;
    hint?: string;
    icon: Component;
    accent?: Accent;
}>();

const accentClasses = computed(() => {
    const map: Record<Accent, string> = {
        amber: 'bg-amber-50 text-amber-600',
        emerald: 'bg-emerald-50 text-emerald-600',
        slate: 'bg-slate-100 text-slate-700',
        red: 'bg-red-100 text-red-700',
        zync: 'bg-slate-100 text-slate-700',
    };

    return map[props.accent ?? 'slate'];
});
</script>

<template>
    <div
        class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
    >
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="text-2xl font-semibold tracking-tight text-slate-900">{{ value }}</p>
                <p v-if="hint" class="text-xs text-slate-400">{{ hint }}</p>
            </div>
            <div :class="['flex h-11 w-11 items-center justify-center rounded-lg', accentClasses]">
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
