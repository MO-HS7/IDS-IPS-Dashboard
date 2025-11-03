<template>
  <teleport to="body">
    <div 
      aria-live="polite" 
      aria-atomic="true" 
      class="fixed inset-0 z-[9999] pointer-events-none"
    >
      <div class="flex flex-col items-end justify-start gap-4 p-4 sm:p-6 h-full overflow-hidden">
        <transition-group
          name="toast"
          tag="div"
          class="flex flex-col items-end gap-4 w-full sm:w-auto max-w-full"
        >
          <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            :duration="toast.duration"
            :persistent="toast.persistent"
            @close="removeToast(toast.id)"
            class="w-full sm:w-auto"
          />
        </transition-group>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { useToast } from '@/Composables/useToast'
import Toast from '@/Components/Toast.vue'

const { toasts, removeToast } = useToast()
</script>

<style scoped>
/* Toast animations - Improved for better stacking */
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 1, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-enter-to {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Prevent layout shift during animations */
.toast-leave-active {
  position: absolute;
  right: 0;
}
</style>
