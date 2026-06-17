import { computed, ref, type ComputedRef, type Ref } from 'vue';
import type {
    PaymentRequest,
    PaymentRequestCollection,
    PaymentRequestStatus,
} from '@/types/payment-request';

export type StatusFilter = 'all' | PaymentRequestStatus;

function normalizePaymentRequests(
    paymentRequests: PaymentRequestCollection,
): PaymentRequest[] {
    if (Array.isArray(paymentRequests)) {
        return paymentRequests;
    }

    return paymentRequests.data ?? [];
}

export function usePaymentRequestFilters(
    paymentRequests: ComputedRef<PaymentRequestCollection> | Ref<PaymentRequestCollection>,
): {
    search: Ref<string>;
    statusFilter: Ref<StatusFilter>;
    filteredRequests: ComputedRef<PaymentRequest[]>;
} {
    const search = ref('');
    const statusFilter = ref<StatusFilter>('all');

    const filteredRequests = computed(() => {
        const data = normalizePaymentRequests(paymentRequests.value);
        let result = [...data];

        if (search.value) {
            const term = search.value.toLowerCase();
            result = result.filter((req) =>
                req.user.name?.toLowerCase().includes(term),
            );
        }

        if (statusFilter.value !== 'all') {
            result = result.filter((req) => req.status === statusFilter.value);
        }

        return result;
    });

    return { search, statusFilter, filteredRequests };
}
