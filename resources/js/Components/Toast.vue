<template>
  <div
    v-if="show"
    :class="[
      'w-full sm:max-w-sm bg-white dark:bg-gray-800 shadow-xl rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 dark:ring-white dark:ring-opacity-10 overflow-hidden',
      typeClasses
    ]"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
  >
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <component :is="iconComponent" :class="iconClasses" aria-hidden="true" />
        </div>
        <div class="ml-3 flex-1 min-w-0 pt-0.5">
          <p class="text-sm font-medium text-gray-900 dark:text-white break-words">{{ title }}</p>
          <p v-if="message" class="mt-1 text-sm text-gray-500 dark:text-gray-400 break-words">{{ message }}</p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="close"
            class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            aria-label="Close notification"
          >
            <span class="sr-only">Close</span>
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon, 
  XCircleIcon,
  XMarkIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    default: ''
  },
  duration: {
    type: Number,
    default: 5000
  },
  persistent: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const show = ref(false)
let timeoutId = null

const typeClasses = computed(() => {
  const classes = {
    success: 'border-l-4 border-green-400',
    error: 'border-l-4 border-red-400',
    warning: 'border-l-4 border-yellow-400',
    info: 'border-l-4 border-blue-400'
  }
  return classes[props.type] || classes.info
})

const iconComponent = computed(() => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[props.type] || icons.info
})

const iconClasses = computed(() => {
  const classes = {
    success: 'h-6 w-6 text-green-400',
    error: 'h-6 w-6 text-red-400',
    warning: 'h-6 w-6 text-yellow-400',
    info: 'h-6 w-6 text-blue-400'
  }
  return classes[props.type] || classes.info
})

const close = () => {
  show.value = false
  if (timeoutId) {
    clearTimeout(timeoutId)
    timeoutId = null
  }
  emit('close')
}

onMounted(() => {
  show.value = true
  
  if (!props.persistent && props.duration > 0) {
    timeoutId = setTimeout(() => {
      close()
    }, props.duration)
  }
})

onUnmounted(() => {
  if (timeoutId) {
    clearTimeout(timeoutId)
  }
})
</script>