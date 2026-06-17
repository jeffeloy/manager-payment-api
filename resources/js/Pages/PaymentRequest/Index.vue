<script setup lang="ts">
import { computed, ref, toRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaymentRequestStats from './Partials/PaymentRequestStats.vue';
import PaymentRequestListCard from './Partials/PaymentRequestListCard.vue';
import CreatePaymentRequestDialog from './Partials/CreatePaymentRequestDialog.vue';
import ViewPaymentRequestDialog from './Partials/ViewPaymentRequestDialog.vue';
import ActionPaymentRequestDialog from './Partials/ActionPaymentRequestDialog.vue';
import { usePaymentRequestFilters } from '@/composables/usePaymentRequestFilters';
import type {
    PaymentRequest,
    PaymentRequestActionType,
    PaymentRequestCollection,
    PaymentRequestStats as PaymentRequestStatsType,
} from '@/types/payment-request';

const props = defineProps<{
    paymentRequests: PaymentRequestCollection;
    stats?: PaymentRequestStatsType;
}>();

const page = usePage();
const isFinance = computed(() => page.props.auth.user.role === 'finance');
const isEmployee = computed(() => page.props.auth.user.role === 'employee');

const { search, statusFilter, filteredRequests } = usePaymentRequestFilters(
    toRef(props, 'paymentRequests'),
);

const emptyStats: PaymentRequestStatsType = {
    pending: 0,
    approved: 0,
    rejected: 0,
    expired: 0,
};

const displayStats = computed(() => props.stats ?? emptyStats);

const selectedRequest = ref<PaymentRequest | null>(null);
const actionType = ref<PaymentRequestActionType>('approve');

const isCreateOpen = ref(false);
const isViewOpen = ref(false);
const isActionOpen = ref(false);

function onView(request: PaymentRequest): void {
    selectedRequest.value = request;
    isViewOpen.value = true;
}

function onOpenAction(request: PaymentRequest, type: PaymentRequestActionType): void {
    selectedRequest.value = request;
    actionType.value = type;
    isActionOpen.value = true;
}
</script>

<template>
    <AppLayout
        title="Payment Requests"
        subtitle="Manage and approve employee payment requests"
    >
        <PaymentRequestStats :stats="displayStats" />

        <PaymentRequestListCard
            v-model:search="search"
            v-model:status-filter="statusFilter"
            :requests="filteredRequests"
            :total-count="filteredRequests.length"
            :is-finance="isFinance"
            :is-employee="isEmployee"
            @create="isCreateOpen = true"
            @view="onView"
            @open-action="onOpenAction"
        />
    </AppLayout>

    <CreatePaymentRequestDialog v-if="isEmployee" v-model:open="isCreateOpen" />

    <ViewPaymentRequestDialog
        v-model:open="isViewOpen"
        :request="selectedRequest"
    />

    <ActionPaymentRequestDialog
        v-model:open="isActionOpen"
        :request="selectedRequest"
        :action-type="actionType"
    />
</template>
