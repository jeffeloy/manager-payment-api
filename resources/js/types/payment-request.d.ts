export type PaymentRequestStatus = 'pending' | 'approved' | 'rejected' | 'expired';

export type PaymentRequestActionType = 'approve' | 'reject';

export interface PaymentRequestUser {
    name: string;
    country?: string;
}

export interface PaymentRequest {
    id: number;
    title: string;
    amount: number;
    currency: string;
    amount_eur: number;
    exchange_rate: number;
    exchange_rate_source: string;
    exchange_rate_fetched_at: string | null;
    status: PaymentRequestStatus;
    rejection_reason: string | null;
    created_at: string;
    user: PaymentRequestUser;
}

export interface PaymentRequestStats {
    pending: number;
    approved: number;
    rejected: number;
    expired: number;
}

export type PaymentRequestCollection =
    | PaymentRequest[]
    | { data: PaymentRequest[] };
