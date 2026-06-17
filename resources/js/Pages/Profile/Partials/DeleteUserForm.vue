<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;
  nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value?.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  form.clearErrors();
  form.reset();
};
</script>

<template>
  <section class="space-y-6">
    <header>
      <h2 class="text-lg font-medium text-gray-900">Delete Account</h2>
      <p class="mt-1 text-sm text-gray-600">
        Once your account is deleted, all of its resources and data will be
        permanently deleted. Before deleting your account, please download any
        data or information that you wish to retain.
      </p>
    </header>

    <Button 
        class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500"
        variant="destructive"
        @click="confirmUserDeletion">
      Delete Account
    </Button>

    <Dialog :open="confirmingUserDeletion" @update:open="confirmingUserDeletion = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Are you sure you want to delete your account?</DialogTitle>
          <DialogDescription>
            Once your account is deleted, all of its resources and data will be
            permanently deleted. Please enter your password to confirm you would
            like to permanently delete your account.
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="space-y-2">
            <Label for="password" class="sr-only">Password</Label>
            <Input
              id="password"
              ref="passwordInput"
              v-model="form.password"
              type="password"
              placeholder="Password"
              @keyup.enter="deleteUser"
            />
            <p v-if="form.errors.password" class="text-sm font-medium text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="closeModal">
            Cancel
          </Button>

          <Button
            class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500"
            variant="destructive"
            :disabled="form.processing"
            @click="deleteUser"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            Delete Account
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </section>
</template>