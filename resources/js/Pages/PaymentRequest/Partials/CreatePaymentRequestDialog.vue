<script setup lang="ts">
import { watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useCurrencyInput, CurrencyDisplay } from 'vue-currency-input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const open = defineModel<boolean>('open', { default: false });

const user = usePage().props.auth.user;

const { inputRef, numberValue } = useCurrencyInput({
    currency: user.currency,
    currencyDisplay: CurrencyDisplay.symbol,
    precision: 2,
    autoDecimalDigits: true,
    valueRange: { min: 0 },
});

const form = useForm({
    amount: '' as string | number,
    currency: user.currency,
    title: '',
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
        form.currency = user.currency;
    }
});

function submitPaymentRequest(): void {
    form
        .transform((data) => ({
            ...data,
            amount: numberValue.value ?? data.amount,
        }))
        .post(route('payment-requests.store'), {
            preserveState: false,
            onSuccess: () => {
                open.value = false;
                form.reset();
            },
        });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>New Payment Request</DialogTitle>
                <DialogDescription>
                    Please fill in the details to request a new payment.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitPaymentRequest" class="mt-4 space-y-4">
                <div class="space-y-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="form.title" placeholder="Title" />
                    <p v-if="form.errors.title" class="text-xs text-red-600">
                        {{ form.errors.title }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="amount">Amount</Label>
                        <input
                            id="amount"
                            ref="inputRef"
                            v-model="form.amount"
                            type="text"
                            placeholder="0.00"
                            class="h-9 w-full rounded-md border border-slate-200 bg-transparent px-2.5 text-sm shadow-xs outline-none focus-visible:border-slate-400 focus-visible:ring-2 focus-visible:ring-slate-400"
                        />
                        <p v-if="form.errors.amount" class="text-xs text-red-600">
                            {{ form.errors.amount }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="currency">Currency</Label>
                        <Input id="currency" v-model="form.currency" disabled />
                    </div>
                </div>

                <Button
                    type="submit"
                    class="w-full bg-slate-900 text-white hover:bg-slate-800"
                    :disabled="form.processing"
                >
                    Submit Request
                </Button>
            </form>
        </DialogContent>
    </Dialog>
</template>
