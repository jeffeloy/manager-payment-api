<script setup lang="ts">
import { MoreHorizontal, Eye, Check, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type {
    PaymentRequest,
    PaymentRequestActionType,
    PaymentRequestStatus,
} from '@/types/payment-request';

defineProps<{
    requests: PaymentRequest[];
    isFinance: boolean;
}>();

defineEmits<{
    view: [request: PaymentRequest];
    'open-action': [request: PaymentRequest, actionType: PaymentRequestActionType];
}>();

function initials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function statusLabel(status: PaymentRequestStatus): string {
    const labels: Record<PaymentRequestStatus, string> = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        expired: 'Expired',
    };

    return labels[status] ?? status;
}

function statusClasses(status: PaymentRequestStatus): string {
    const classes: Record<PaymentRequestStatus, string> = {
        pending: 'border-amber-200 bg-amber-50 text-amber-700',
        approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-200 bg-rose-50 text-rose-700',
        expired: 'border-slate-200 bg-slate-50 text-slate-600',
    };

    return classes[status];
}

function dotClasses(status: PaymentRequestStatus): string {
    const classes: Record<PaymentRequestStatus, string> = {
        pending: 'bg-amber-500',
        approved: 'bg-emerald-500',
        rejected: 'bg-rose-500',
        expired: 'bg-slate-500',
    };

    return classes[status];
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
}

function formatLocal(value: number, currency: string): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(value);
}

function formatEUR(value: number): string {
    return new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(value);
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow class="border-slate-100 hover:bg-transparent">
                <TableHead
                    class="px-6 text-xs font-medium uppercase tracking-wide text-slate-500"
                >
                    Name
                </TableHead>
                <TableHead class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Amount (Local)
                </TableHead>
                <TableHead class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Converted (EUR)
                </TableHead>
                <TableHead class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Currency
                </TableHead>
                <TableHead class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Date
                </TableHead>
                <TableHead class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Status
                </TableHead>
                <TableHead
                    class="px-6 text-right text-xs font-medium uppercase tracking-wide text-slate-500"
                >
                    Actions
                </TableHead>
            </TableRow>
        </TableHeader>

        <TableBody>
            <TableRow
                v-for="req in requests"
                :key="req.id"
                class="border-slate-100 transition-colors hover:bg-slate-50/60"
            >
                <TableCell class="px-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-medium text-slate-600"
                        >
                            {{ initials(req.user.name) }}
                        </div>
                        <div>
                            <p class="font-medium text-slate-900">{{ req.user.name }}</p>
                            <p v-if="req.user.country" class="text-xs text-slate-400">
                                {{ req.user.country }}
                            </p>
                        </div>
                    </div>
                </TableCell>

                <TableCell class="font-medium tabular-nums">
                    {{ formatLocal(req.amount, req.currency) }}
                </TableCell>

                <TableCell class="tabular-nums text-slate-600">
                    {{ formatEUR(req.amount_eur) }}
                </TableCell>

                <TableCell>
                    <span
                        class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                    >
                        {{ req.currency }}
                    </span>
                </TableCell>

                <TableCell class="text-sm text-slate-500">
                    {{ formatDate(req.created_at) }}
                </TableCell>

                <TableCell>
                    <Badge variant="outline" :class="['rounded-sm', statusClasses(req.status)]">
                        <span
                            :class="['mr-1.5 h-1.5 w-1.5 rounded-full', dotClasses(req.status)]"
                        />
                        {{ statusLabel(req.status) }}
                    </Badge>
                </TableCell>

                <TableCell class="px-6 text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500">
                                <MoreHorizontal class="h-4 w-4" />
                                <span class="sr-only">Open actions</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuItem @click="$emit('view', req)">
                                <Eye class="mr-2 h-4 w-4" />
                                View Details
                            </DropdownMenuItem>

                            <template v-if="isFinance && req.status === 'pending'">
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    class="text-emerald-600 focus:text-emerald-700"
                                    @click.prevent="$emit('open-action', req, 'approve')"
                                >
                                    <Check class="mr-2 h-4 w-4" />
                                    Approve
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    class="text-rose-600 focus:text-rose-700"
                                    @click.prevent="$emit('open-action', req, 'reject')"
                                >
                                    <X class="mr-2 h-4 w-4" />
                                    Reject
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </TableCell>
            </TableRow>

            <TableRow v-if="requests.length === 0">
                <TableCell colspan="7" class="py-12 text-center text-sm text-slate-400">
                    No payment requests found.
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
