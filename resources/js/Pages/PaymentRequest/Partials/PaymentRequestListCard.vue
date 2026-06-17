<script setup lang="ts">
import PaymentTable from './PaymentTable.vue';
import PaymentRequestToolbar from './PaymentRequestToolbar.vue';
import type { StatusFilter } from '@/composables/usePaymentRequestFilters';
import type {
    PaymentRequest,
    PaymentRequestActionType,
} from '@/types/payment-request';

defineProps<{
    requests: PaymentRequest[];
    totalCount: number;
    isFinance: boolean;
    isEmployee: boolean;
}>();

const search = defineModel<string>('search', { default: '' });
const statusFilter = defineModel<StatusFilter>('statusFilter', { default: 'all' });

defineEmits<{
    create: [];
    view: [request: PaymentRequest];
    'open-action': [request: PaymentRequest, actionType: PaymentRequestActionType];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-semibold">All Requests</h2>
                <p class="text-xs text-slate-500">{{ totalCount }} total requests</p>
            </div>

            <PaymentRequestToolbar
                v-model:search="search"
                v-model:status-filter="statusFilter"
                :is-employee="isEmployee"
                @create="$emit('create')"
            />
        </div>

        <PaymentTable
            :requests="requests"
            :is-finance="isFinance"
            @view="$emit('view', $event)"
            @open-action="(request, actionType) => $emit('open-action', request, actionType)"
        />
    </div>
</template>
