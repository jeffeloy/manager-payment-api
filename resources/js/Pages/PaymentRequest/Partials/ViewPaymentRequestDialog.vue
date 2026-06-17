<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { PaymentRequest } from '@/types/payment-request';

const open = defineModel<boolean>('open', { default: false });

defineProps<{
    request: PaymentRequest | null;
}>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Detalhes da Solicitação #{{ request?.id }}</DialogTitle>
            </DialogHeader>

            <div v-if="request" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-slate-500">Status</Label>
                        <div class="font-medium capitalize">{{ request.status }}</div>
                    </div>
                    <div>
                        <Label class="text-xs text-slate-500">Criado em</Label>
                        <div class="text-sm font-medium">
                            {{ new Date(request.created_at).toLocaleDateString() }}
                        </div>
                    </div>
                </div>

                <div>
                    <Label class="text-xs text-slate-500">Título</Label>
                    <div class="font-medium">{{ request.title }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-slate-500">Valor</Label>
                        <div class="font-medium">
                            {{ request.amount }} {{ request.currency }}
                        </div>
                    </div>
                    <div>
                        <Label class="text-xs text-slate-500">Taxa de Câmbio</Label>
                        <div class="font-medium">{{ request.exchange_rate }}</div>
                    </div>
                </div>

                <div v-if="request.rejection_reason" class="rounded-lg bg-red-50 p-3">
                    <Label class="text-xs font-bold text-red-800">Motivo da Rejeição</Label>
                    <p class="text-sm text-red-700">{{ request.rejection_reason }}</p>
                </div>

                <div class="mt-2 border-t pt-4 text-[10px] text-slate-400">
                    Fonte: {{ request.exchange_rate_source }} |
                    Consultado em:
                    {{
                        request.exchange_rate_fetched_at
                            ? new Date(request.exchange_rate_fetched_at).toLocaleString()
                            : '—'
                    }}
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
