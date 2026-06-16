<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Wallet, Loader2, MailCheck } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  status?: string
}>()

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(
  () => props.status === 'verification-link-sent',
)
</script>

<template>
  <Head title="Email Verification" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
      <!-- Brand -->
      <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Wallet class="h-6 w-6" />
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Verify your email</h1>
        <p class="mt-1 text-sm leading-relaxed text-slate-500">
          Thanks for signing up! Please verify your email by clicking the link we just sent you. Didn&apos;t
          receive it? We&apos;ll gladly send another.
        </p>
      </div>

      <!-- Card -->
      <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <!-- Session status -->
        <div
          v-if="verificationLinkSent"
          class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
          <MailCheck class="h-5 w-5 shrink-0" />
          A new verification link has been sent to your email address.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
          <!-- Resend -->
          <Button
            type="submit"
            class="w-full"
            :class="{ 'opacity-70': form.processing }"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            {{ form.processing ? 'Sending...' : 'Resend verification email' }}
          </Button>

          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="block w-full text-center text-sm font-medium text-slate-500 underline-offset-4 transition-colors hover:text-slate-900 hover:underline"
          >
            Log out
          </Link>
        </form>
      </div>
    </div>
  </div>
</template>
