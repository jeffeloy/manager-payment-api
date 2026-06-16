<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Wallet, Loader2, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

defineProps<{
  status?: string
}>()

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <Head title="Forgot Password" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
      <!-- Brand -->
      <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Wallet class="h-6 w-6" />
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Forgot your password?</h1>
        <p class="mt-1 text-sm leading-relaxed text-slate-500">
          No problem. Enter your email and we&apos;ll send you a link to reset it.
        </p>
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

          <!-- Submit -->
          <Button
            type="submit"
            class="w-full"
            :class="{ 'opacity-70': form.processing }"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            {{ form.processing ? 'Sending...' : 'Email password reset link' }}
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
