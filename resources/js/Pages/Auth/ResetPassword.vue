<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Wallet, Loader2, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps<{
  email: string
  token: string
}>()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Reset Password" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
      <!-- Brand -->
      <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Wallet class="h-6 w-6" />
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Reset your password</h1>
        <p class="mt-1 text-sm leading-relaxed text-slate-500">
          Choose a new password for your account.
        </p>
      </div>

      <!-- Card -->
      <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <form class="space-y-5" @submit.prevent="submit">
          <!-- Email -->
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="username"
              placeholder="you@company.com"
            />
            <p v-if="form.errors.email" class="text-sm font-medium text-rose-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <Label for="password">New password</Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              required
              autofocus
              autocomplete="new-password"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="text-sm font-medium text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Confirm password -->
          <div class="space-y-2">
            <Label for="password_confirmation">Confirm new password</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password_confirmation" class="text-sm font-medium text-rose-600">
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <!-- Submit -->
          <Button
            type="submit"
            class="w-full"
            :class="{ 'opacity-70': form.processing }"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            {{ form.processing ? 'Resetting...' : 'Reset password' }}
          </Button>
        </form>
      </div>

      <p class="mt-6 text-center">
        <Link
          :href="route('login')"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 underline-offset-4 transition-colors hover:text-slate-900 hover:underline"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to log in
        </Link>
      </p>
    </div>
  </div>
</template>
