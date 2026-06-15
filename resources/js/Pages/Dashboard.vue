<template>
    <div class="flex min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <!-- Sidebar -->
        <aside :class="[
            'flex flex-col border-r border-slate-200 bg-white transition-all duration-300 ease-in-out',
            collapsed ? 'w-20' : 'w-64',
        ]">
            <!-- Brand -->
            <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white">
                    <Wallet class="h-5 w-5" />
                </div>
                <span v-if="!collapsed" class="truncate text-lg font-semibold tracking-tight">PayFlow</span>
            </div>

            <!-- Nav -->
            <nav class="flex flex-1 flex-col gap-1 p-3">
                <button v-for="item in navItems" :key="item.key" type="button" :class="[
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                    activeNav === item.key
                        ? 'bg-slate-900 text-white'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]" @click="activeNav = item.key">
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                </button>
            </nav>

            <!-- Collapse toggle -->
            <div class="border-t border-slate-100 p-3">
                <button type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    @click="collapsed = !collapsed">
                    <PanelLeftClose v-if="!collapsed" class="h-5 w-5 shrink-0" />
                    <PanelLeftOpen v-else class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">Collapse</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex flex-1 flex-col">
            <!-- Topbar -->
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">Payment Requests</h1>
                    <p class="text-xs text-slate-500">Manage and approve employee payment requests</p>
                </div>
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <!-- Settings Dropdown -->
                    <div class="relative ms-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                        {{ $page.props.auth.user.name }}

                                        <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 space-y-6 p-6">
                <!-- Stats -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <StatCard label="Total Pending" :value="displayStats.pending.toString()" hint="Awaiting approval"
                        :icon="Clock" accent="amber" />
                    <StatCard label="Total Approved" :value="displayStats.approved.toString()" hint="This month"
                        :icon="CheckCircle2" accent="emerald" />
                    <StatCard label="Total Rejected" :value="displayStats.rejected.toString()"
                        hint="Rejected this month" :icon="CircleX" accent="red" />
                </div>

                <!-- Table card -->
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div>
                            <h2 class="text-sm font-semibold">All Requests</h2>
                            <p class="text-xs text-slate-500">{{ requests.length }} total requests</p>
                        </div>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input v-model="search" type="text" placeholder="Search employee..."
                                class="h-9 w-56 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm outline-none transition focus:border-slate-400 focus:bg-white" />
                        </div>
                    </div>

                    <PaymentTable :requests="filteredRequests" :is-finance="isFinance" @view="onView"
                        @approve="onApprove" @reject="onReject" />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, toRaw } from 'vue'
import {
    Wallet,
    LayoutDashboard,
    FileText,
    CheckSquare,
    PanelLeftClose,
    PanelLeftOpen,
    Clock,
    CheckCircle2,
    Search,
    CircleX,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import StatCard from './StatCard.vue'
import PaymentTable from './PaymentTable.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'

const props = defineProps({
    paymentRequests: {
        type: Object,
        required: true
    },
    stats: Object
});

const collapsed = ref(false)
const isFinance = ref(true)
const activeNav = ref('dashboard')
const search = ref('')

const navItems = computed(() => [
    { key: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { key: 'requests', label: 'My Requests', icon: FileText },
    ...(isFinance.value
        ? [{ key: 'approve', label: 'Approve Requests', icon: CheckSquare }]
        : []),
])

const requests = toRaw(props.paymentRequests.data);

const filteredRequests = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return requests
    return requests;
})

const displayStats = computed(() => {
    const stats = toRaw(props.stats);
    if (stats) return stats;
    return {
        pending: requests.value.filter((r) => r.status === 'pending').length,
        approved: requests.value.filter((r) => r.status === 'approved').length,
        volume: requests.value
            .filter((r) => r.status === 'approved')
            .reduce((sum, r) => sum + (Number(r.amount_eur) || 0), 0),
    }
})

function onView(req) {
    console.log('[v0] View details:', req)
}
function onApprove(req) {
    const target = requests.value.find((r) => r.id === req.id)
    if (target) target.status = 'approved'
}
function onReject(req) {
    const target = requests.value.find((r) => r.id === req.id)
    if (target) target.status = 'rejected'
}
</script>