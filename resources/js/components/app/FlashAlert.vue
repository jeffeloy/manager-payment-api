<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, TriangleAlert, X } from 'lucide-vue-next';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const dismissed = ref(false);

const successMessage = computed(() => page.props.flash?.success ?? '');
const errorMessage = computed(() => page.props.flash?.error ?? '');
const hasError = computed(() => errorMessage.value.length > 0);
const message = computed(() => errorMessage.value || successMessage.value);

let dismissTimer: ReturnType<typeof setTimeout> | null = null;

function clearTimer(): void {
    if (dismissTimer) {
        clearTimeout(dismissTimer);
        dismissTimer = null;
    }
}

watch(
    () => message.value,
    (newMessage) => {
        dismissed.value = false;
        clearTimer();

        if (newMessage) {
            dismissTimer = setTimeout(() => {
                dismissed.value = true;
            }, 5000);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    clearTimer();
});

const visible = computed(() => message.value.length > 0 && !dismissed.value);
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-1 opacity-0"
    >
        <div
            v-if="visible"
            :class="[
                'mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm',
                hasError
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ]"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-start gap-2">
                <TriangleAlert v-if="hasError" class="mt-0.5 h-4 w-4 shrink-0" />
                <CircleCheck v-else class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ message }}</span>
            </div>

            <button
                type="button"
                class="rounded p-0.5 text-current/70 transition hover:bg-black/5 hover:text-current"
                @click="dismissed = true"
            >
                <X class="h-4 w-4" />
                <span class="sr-only">Dismiss</span>
            </button>
        </div>
    </transition>
</template>
