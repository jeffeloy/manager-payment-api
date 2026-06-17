<script setup lang="ts">
import { CheckCircle2, CircleX, Clock, TimerOff } from 'lucide-vue-next';
import StatCard from './StatCard.vue';
import type { PaymentRequestStats } from '@/types/payment-request';

defineProps<{
    stats: PaymentRequestStats;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
            label="Total Pending"
            :value="stats.pending.toString()"
            :icon="Clock"
            accent="amber"
        />
        <StatCard
            label="Total Approved"
            :value="stats.approved.toString()"
            :icon="CheckCircle2"
            accent="emerald"
        />
        <StatCard
            label="Total Rejected"
            :value="stats.rejected.toString()"
            :icon="CircleX"
            accent="red"
        />
        <StatCard
            label="Total Expired"
            :value="stats.expired.toString()"
            :icon="TimerOff"
            accent="zync"
        />
    </div>
</template>
