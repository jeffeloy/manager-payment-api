<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogOut, User } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name ?? '';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
            >
                <span
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-medium text-white"
                >
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
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full text-rose-600 focus:text-rose-700"
                >
                    <LogOut class="h-4 w-4" />
                    Log Out
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
