<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Wallet, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const countries = [
  { code: 'PT', name: 'Portugal' },
  { code: 'ES', name: 'Spain' },
  { code: 'FR', name: 'France' },
  { code: 'DE', name: 'Germany' },
  { code: 'IE', name: 'Ireland' },
  { code: 'IT', name: 'Italy' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'BE', name: 'Belgium' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'US', name: 'United States' },
  { code: 'BR', name: 'Brazil' },
  { code: 'JP', name: 'Japan' },
]

const form = useForm({
  name: '',
  email: '',
  country: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

<template>
  <Head title="Register" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
      <!-- Brand -->
      <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Wallet class="h-6 w-6" />
        </div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Create your account</h1>
        <p class="mt-1 text-sm text-slate-500">Get started with PayFlow today</p>
      </div>

      <!-- Card -->
      <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <form class="space-y-5" @submit.prevent="submit">
          <!-- Name -->
          <div class="space-y-2">
            <Label for="name">Name</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              required
              autofocus
              autocomplete="name"
              placeholder="John Doe"
            />
            <p v-if="form.errors.name" class="text-sm font-medium text-rose-600">
              {{ form.errors.name }}
            </p>
          </div>

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

          <!-- Country -->
          <div class="space-y-2">
            <Label for="country">Country</Label>
            <Select v-model="form.country">
              <SelectTrigger id="country" class="w-full">
                <SelectValue placeholder="Select your country" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="country in countries"
                  :key="country.code"
                  :value="country.code"
                >
                  {{ country.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.country" class="text-sm font-medium text-rose-600">
              {{ form.errors.country }}
            </p>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <Label for="password">Password</Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="new-password"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="text-sm font-medium text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div class="space-y-2">
            <Label for="password_confirmation">Confirm Password</Label>
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
            {{ form.processing ? 'Creating account...' : 'Create account' }}
          </Button>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        Already registered?
        <Link
          :href="route('login')"
          class="font-medium text-slate-900 underline-offset-4 hover:underline"
        >
          Log in
        </Link>
      </p>
    </div>
  </div>
</template>
