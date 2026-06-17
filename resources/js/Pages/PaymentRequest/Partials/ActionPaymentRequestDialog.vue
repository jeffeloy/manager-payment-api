<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import type {
    PaymentRequest,
    PaymentRequestActionType,
} from '@/types/payment-request';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{
    request: PaymentRequest | null;
    actionType: PaymentRequestActionType;
}>();

const form = useForm({
    rejection_reason: '',
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.rejection_reason = '';
        form.clearErrors();
    }
});

function performAction(): void {
    if (!props.request) {
        return;
    }

    const routeName =
        props.actionType === 'approve'
            ? 'payment-requests.approve'
            : 'payment-requests.reject';

    form.patch(route(routeName, props.request.id), {
        preserveState: false,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>
                    {{
                        actionType === 'approve'
                            ? 'Approve Request'
                            : 'Reject Request'
                    }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="performAction" class="space-y-4">
                <p class="text-sm text-slate-500">
                    Are you sure you want to
                    {{ actionType === 'approve' ? 'approve' : 'reject' }}
                    <strong>{{ request?.user?.name }}</strong>'s request?
                </p>

                <div v-if="actionType === 'reject'" class="space-y-2">
                    <Label for="rejection_reason">Rejection Reason</Label>
                    <Textarea
                        id="rejection_reason"
                        v-model="form.rejection_reason"
                        required
                        placeholder="Explain why this request is being rejected..."
                    />
                </div>

                <DialogFooter>
                    <Button variant="ghost" type="button" @click="open = false">
                        Cancel
                    </Button>
                    <Button
                        :variant="actionType === 'approve' ? 'default' : 'destructive'"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Confirm
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
