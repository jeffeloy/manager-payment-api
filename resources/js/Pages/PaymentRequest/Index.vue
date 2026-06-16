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
                <!-- Dashboard -->
                <Link :href="route('dashboard')"
                    :class="['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        route().current('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900']">
                    <LayoutDashboard class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">Dashboard</span>
                </Link>
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
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-medium text-white">
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
                                <Link :href="route('logout')" method="post" as="button"
                                    class="w-full text-rose-600 focus:text-rose-700">
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
                    <StatCard label="Total Pending" :value="displayStats.pending.toString()" :icon="Clock"
                        accent="amber" />
                    <StatCard label="Total Approved" :value="displayStats.approved.toString()" :icon="CheckCircle2"
                        accent="emerald" />
                    <StatCard label="Total Rejected" :value="displayStats.rejected.toString()" :icon="CircleX"
                        accent="red" />
                </div>

                <!-- Table card -->
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div>
                            <h2 class="text-sm font-semibold">All Requests</h2>
                            <p class="text-xs text-slate-500">{{ requests.length }} total requests</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input v-model="search" type="text" placeholder="Search employee..."
                                    class="h-9 w-56 rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm outline-none transition focus:border-slate-400 focus:bg-white" />
                            </div>

                            <Button variant="ghost" size="sm" @click="isCreateModalOpen = true"
                                class="h-9 gap-1.5 rounded-lg border border-slate-200 bg-slate-950 hover:bg-slate-900 text-white hover:text-slate-50">
                                <PlusCircle class="h-4 w-4" />
                                Create
                            </Button>
                        </div>
                    </div>

                    <PaymentTable :requests="filteredRequests" :is-finance="$page.props.auth.user.role === 'finance'"
                        @view="onView" @open-action="openActionModal" />
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Nova Solicitação -->
    <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Nova Solicitação de Pagamento</DialogTitle>
                <DialogDescription>
                    Preencha os detalhes para solicitar um novo pagamento.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitPaymentRequest" class="space-y-4 mt-4">
                <div class="space-y-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="createForm.title" placeholder="Title" />
                    <p v-if="createForm.errors.title" class="text-xs text-red-600">{{
                        createForm.errors.title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="amount">Valor</Label>
                        <Input id="amount" v-model="createForm.amount" type="number" step="0.01" placeholder="0.00" />
                        <p v-if="createForm.errors.amount" class="text-xs text-red-600">{{ createForm.errors.amount }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="currency">Currency</Label>
                        <Input id="currency" v-model="createForm.currency" disabled />
                    </div>
                </div>

                <!-- Botão com as cores forçadas para evitar o erro do background transparente -->
                <Button type="submit" class="w-full bg-slate-900 text-white hover:bg-slate-800"
                    :disabled="createForm.processing">
                    Enviar Solicitação
                </Button>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog :open="isViewModalOpen" @update:open="isViewModalOpen = $event">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Detalhes da Solicitação #{{ selectedRequest?.id }}</DialogTitle>
            </DialogHeader>

            <div v-if="selectedRequest" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-slate-500">Status</Label>
                        <div class="font-medium capitalize">{{ selectedRequest.status }}</div>
                    </div>
                    <div>
                        <Label class="text-xs text-slate-500">Criado em</Label>
                        <div class="font-medium text-sm">
                            {{ new Date(selectedRequest.created_at).toLocaleDateString() }}
                        </div>
                    </div>
                </div>

                <div>
                    <Label class="text-xs text-slate-500">Título</Label>
                    <div class="font-medium">{{ selectedRequest.title }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-slate-500">Valor</Label>
                        <div class="font-medium">{{ selectedRequest.amount }} {{ selectedRequest.currency }}</div>
                    </div>
                    <div>
                        <Label class="text-xs text-slate-500">Taxa de Câmbio</Label>
                        <div class="font-medium">{{ selectedRequest.exchange_rate }}</div>
                    </div>
                </div>

                <div v-if="selectedRequest.rejection_reason" class="bg-red-50 p-3 rounded-lg">
                    <Label class="text-xs text-red-800 font-bold">Motivo da Rejeição</Label>
                    <p class="text-sm text-red-700">{{ selectedRequest.rejection_reason }}</p>
                </div>

                <div class="border-t pt-4 mt-2 text-[10px] text-slate-400">
                    Fonte: {{ selectedRequest.exchange_rate_source }} |
                    Consultado em: {{ new Date(selectedRequest.exchange_rate_fetched_at).toLocaleString() }}
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog :open="isActionModalOpen" @update:open="isActionModalOpen = $event">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>
                    {{ actionType === 'approve' ? 'Aprovar Solicitação' : 'Rejeitar Solicitação' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="performAction" class="space-y-4">
                <p class="text-sm text-slate-500">
                    Tem certeza que deseja {{ actionType === 'approve' ? 'aprovar' : 'rejeitar' }} a solicitação de
                    <strong>{{ selectedRequest?.employee }}</strong>?
                </p>

                <div v-if="actionType === 'reject'" class="space-y-2">
                    <Label for="rejection_reason">Motivo da Rejeição</Label>
                    <Textarea id="rejection_reason" v-model="actionForm.rejection_reason" required
                        placeholder="Explique o porquê da rejeição..." />
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <Button variant="ghost" type="button" @click="isActionModalOpen = false">Cancelar</Button>
                    <Button :variant="actionType === 'approve' ? 'default' : 'destructive'"
                        :disabled="actionForm.processing">
                        Confirmar
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
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
    PlusCircle
} from 'lucide-vue-next'
import StatCard from './Partials/StatCard.vue'
import PaymentTable from './Partials/PaymentTable.vue'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { usePage, useForm } from '@inertiajs/vue3';

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

function onApprove(req) {
    const target = requests.find((r) => r.id === req.id)
    if (target) target.status = 'approved'
}
function onReject(req) {
    const target = requests.find((r) => r.id === req.id)
    if (target) target.status = 'rejected'
}

const isCreateModalOpen = ref(false);
const user = page.props.auth.user;

const createForm = useForm({
    amount: '',
    currency: user.currency,
    title: '',
});

const submitPaymentRequest = () => {
    createForm.post(route('payment-requests.store'), {
        preserveState: false,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        }
    });
};

const selectedRequest = ref(null);
const isViewModalOpen = ref(false);

const onView = (req) => {
    selectedRequest.value = req;
    isViewModalOpen.value = true;
};

const actionForm = useForm({
    rejection_reason: '',
});
const actionType = ref('');
const isActionModalOpen = ref(false);

const openActionModal = (req, type) => {
    selectedRequest.value = req;
    actionType.value = type;
    actionForm.rejection_reason = ''; // Limpa o campo
    isActionModalOpen.value = true;
};

const performAction = () => {
    const routeName = actionType.value === 'approve'
        ? 'payment-requests.approve'
        : 'payment-requests.reject';

    actionForm.patch(route(routeName, selectedRequest.value.id), {
        onSuccess: () => {
            isActionModalOpen.value = false;
        }
    });
};
</script>
