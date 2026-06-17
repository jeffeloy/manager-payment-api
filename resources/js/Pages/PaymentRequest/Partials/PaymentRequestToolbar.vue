<script setup lang="ts">
import { PlusCircle, Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { StatusFilter } from '@/composables/usePaymentRequestFilters';

defineProps<{
    isEmployee: boolean;
}>();

const search = defineModel<string>('search', { default: '' });
const statusFilter = defineModel<StatusFilter>('statusFilter', { default: 'all' });

defineEmits<{
    create: [];
}>();
</script>

<template>
    <div class="flex items-center gap-3">
        <select
            v-model="statusFilter"
            class="h-9 rounded-md border border-slate-200 bg-slate-50 px-6 text-sm outline-none focus:border-slate-400 focus:bg-white"
        >
            <option value="all">All status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="expired">Expired</option>
        </select>

        <div class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                v-model="search"
                type="text"
                placeholder="Search employee..."
                class="h-9 w-56 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm outline-none transition focus:border-slate-400 focus:bg-white"
            />
        </div>

        <Button
            v-if="isEmployee"
            variant="ghost"
            size="sm"
            class="h-9 gap-1.5 rounded-lg border border-slate-200 bg-slate-950 text-white hover:bg-slate-900 hover:text-slate-50"
            @click="$emit('create')"
        >
            <PlusCircle class="h-4 w-4" />
            Create
        </Button>
    </div>
</template>
