<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Wallet, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'

defineProps<{
  canResetPassword?: boolean
  status?: string
}>()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => {
      form.reset('password')
    },
  })
}
</script>

<template>
  <Head title="Log in" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
      <!-- Brand -->
      <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Wallet class="h-6 w-6" />
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Welcome back</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to your PayFlow account</p>
      </div>

      <!-- Card -->
      <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <!-- Session status -->
        <div
          v-if="status"
          class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
          {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <!-- Email -->
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              autocomplete="username"
              placeholder="you@company.com"
            />
            <p v-if="form.errors.email" class="text-sm font-medium text-rose-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <Label for="password">Password</Label>
              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm font-medium text-slate-500 underline-offset-4 transition-colors hover:text-slate-900 hover:underline"
              >
                Forgot password?
              </Link>
            </div>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="text-sm font-medium text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember me -->
          <div class="flex items-center gap-2">
            <Checkbox id="remember" v-model:checked="form.remember" />
            <Label for="remember" class="text-sm font-normal text-slate-600">Remember me</Label>
          </div>

          <!-- Submit -->
          <Button
            type="submit"
            class="w-full"
            :class="{ 'opacity-70': form.processing }"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            {{ form.processing ? 'Signing in...' : 'Log in' }}
          </Button>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        Don't have an account?
        <Link
          :href="route('register')"
          class="font-medium text-slate-900 underline-offset-4 hover:underline"
        >
          Create one
        </Link>
      </p>
    </div>
  </div>
</template>
