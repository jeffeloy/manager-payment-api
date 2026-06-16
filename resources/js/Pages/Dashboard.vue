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
                    <!-- User menu (shadcn-vue DropdownMenu) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-medium text-white">
                                    {{ userInitials }}
                                </span>
                                {{ $page.props.auth.user.name }}
                                <ChevronDown class="h-4 w-4 text-slate-400" />
                            </button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuLabel>{{ $page.props.auth.user.email }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="route('profile.edit')" class="w-full">
                                    <User class="h-4 w-4 text-slate-400" />
                                    Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="route('logout')" method="post" as="button" class="w-full text-rose-600 focus:text-rose-700">
                                    <LogOut class="h-4 w-4" />
                                    Log Out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
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
import { Link } from '@inertiajs/vue3'
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
    ChevronDown,
    User,
    LogOut,
} from 'lucide-vue-next'
import StatCard from './StatCard.vue'
import PaymentTable from './PaymentTable.vue'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    paymentRequests: {
        type: Object,
        required: true
    },
    stats: Object
});

const page = usePage()

const collapsed = ref(false)
const isFinance = ref(true)
const activeNav = ref('dashboard')
const search = ref('')

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name ?? ''
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
})

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
    return requests.filter((r) => (r.employee ?? '').toLowerCase().includes(q))
})

const displayStats = computed(() => {
    const stats = toRaw(props.stats);
    if (stats) return stats;
    return {
        pending: requests.filter((r) => r.status === 'pending').length,
        approved: requests.filter((r) => r.status === 'approved').length,
        rejected: requests.filter((r) => r.status === 'rejected').length,
    }
})

function onView(req) {
    console.log('[v0] View details:', req)
}
function onApprove(req) {
    const target = requests.find((r) => r.id === req.id)
    if (target) target.status = 'approved'
}
function onReject(req) {
    const target = requests.find((r) => r.id === req.id)
    if (target) target.status = 'rejected'
}
</script>
