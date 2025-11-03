<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from '@/Composables/useToast'
import {
  UserCircleIcon,
  LockClosedIcon,
  BellIcon,
  PaintBrushIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  EyeIcon,
  EyeSlashIcon
} from '@heroicons/vue/24/outline'

const { success, error } = useToast()
const page = usePage()

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// Handle flash messages
const checkFlashMessages = () => {
  if (page.props.flash?.success) {
    success('Success', page.props.flash.success)
  }
  if (page.props.flash?.error) {
    error('Error', page.props.flash.error)
  }
}

onMounted(() => {
  checkFlashMessages()
})

watch(() => page.props.flash, () => {
  checkFlashMessages()
}, { deep: true })

// Active tab state
const activeTab = ref('profile')

// Profile Form
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
})

// Profile validation
const profileErrors = ref({})
const validateProfile = () => {
  profileErrors.value = {}
  
  if (!profileForm.name || profileForm.name.trim() === '') {
    profileErrors.value.name = 'Name is required'
  } else if (profileForm.name.length < 2) {
    profileErrors.value.name = 'Name must be at least 2 characters'
  } else if (profileForm.name.length > 255) {
    profileErrors.value.name = 'Name must not exceed 255 characters'
  }
  
  if (!profileForm.email || profileForm.email.trim() === '') {
    profileErrors.value.email = 'Email is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(profileForm.email)) {
    profileErrors.value.email = 'Please enter a valid email address'
  }
  
  return Object.keys(profileErrors.value).length === 0
}

// Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Password visibility toggles
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Password validation
const passwordErrors = ref({})
const passwordStrength = computed(() => {
  const pass = passwordForm.password
  if (!pass) return { strength: 0, label: '', color: '' }
  
  let strength = 0
  if (pass.length >= 8) strength++
  if (pass.length >= 12) strength++
  if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) strength++
  if (/\d/.test(pass)) strength++
  if (/[^a-zA-Z0-9]/.test(pass)) strength++
  
  const levels = {
    0: { label: 'Very Weak', color: 'bg-red-500' },
    1: { label: 'Weak', color: 'bg-orange-500' },
    2: { label: 'Fair', color: 'bg-yellow-500' },
    3: { label: 'Good', color: 'bg-blue-500' },
    4: { label: 'Strong', color: 'bg-green-500' },
    5: { label: 'Very Strong', color: 'bg-green-600' }
  }
  
  return { strength, ...levels[strength] }
})

const validatePassword = () => {
  passwordErrors.value = {}
  
  if (!passwordForm.current_password) {
    passwordErrors.value.current_password = 'Current password is required'
  }
  
  if (!passwordForm.password) {
    passwordErrors.value.password = 'New password is required'
  } else if (passwordForm.password.length < 8) {
    passwordErrors.value.password = 'Password must be at least 8 characters'
  }
  
  if (!passwordForm.password_confirmation) {
    passwordErrors.value.password_confirmation = 'Please confirm your password'
  } else if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordErrors.value.password_confirmation = 'Passwords do not match'
  }
  
  return Object.keys(passwordErrors.value).length === 0
}

// Theme Settings
const darkMode = ref(localStorage.getItem('darkMode') === 'true')
const emailNotifications = ref(localStorage.getItem('emailNotifications') !== 'false')
const pushNotifications = ref(localStorage.getItem('pushNotifications') !== 'false')
const securityAlerts = ref(localStorage.getItem('securityAlerts') !== 'false')

// Form submission handlers
const updateProfile = () => {
  if (!validateProfile()) {
    error('Validation Error', 'Please fix the errors in the form')
    return
  }
  
  profileForm.put('/settings/profile', {
    preserveScroll: true,
    onError: (errors) => {
      profileErrors.value = errors
      const firstError = Object.values(errors)[0]
      if (firstError) {
        error('Validation Error', firstError)
      }
    }
  })
}

const updatePassword = () => {
  if (!validatePassword()) {
    error('Validation Error', 'Please fix the errors in the form')
    return
  }

  passwordForm.put('/settings/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      passwordErrors.value = {}
      showCurrentPassword.value = false
      showNewPassword.value = false
      showConfirmPassword.value = false
    },
    onError: (errors) => {
      passwordErrors.value = errors
      const firstError = Object.values(errors)[0]
      if (firstError) {
        error('Validation Error', firstError)
      }
    }
  })
}

// Theme and notification handlers
const toggleDarkMode = () => {
  darkMode.value = !darkMode.value
  localStorage.setItem('darkMode', darkMode.value)
  document.documentElement.classList.toggle('dark')
  success('Success', `Dark mode ${darkMode.value ? 'enabled' : 'disabled'}`)
}

const toggleEmailNotifications = () => {
  emailNotifications.value = !emailNotifications.value
  localStorage.setItem('emailNotifications', emailNotifications.value)
  success('Success', `Email notifications ${emailNotifications.value ? 'enabled' : 'disabled'}`)
}

const togglePushNotifications = () => {
  pushNotifications.value = !pushNotifications.value
  localStorage.setItem('pushNotifications', pushNotifications.value)
  success('Success', `Push notifications ${pushNotifications.value ? 'enabled' : 'disabled'}`)
}

const toggleSecurityAlerts = () => {
  securityAlerts.value = !securityAlerts.value
  localStorage.setItem('securityAlerts', securityAlerts.value)
  success('Success', `Security alerts ${securityAlerts.value ? 'enabled' : 'disabled'}`)
}

// Tab navigation
const tabs = [
  { id: 'profile', name: 'Profile', icon: UserCircleIcon, description: 'Update your personal information' },
  { id: 'security', name: 'Security', icon: LockClosedIcon, description: 'Change your password' },
  { id: 'notifications', name: 'Notifications', icon: BellIcon, description: 'Manage notification preferences' },
  { id: 'appearance', name: 'Appearance', icon: PaintBrushIcon, description: 'Customize your theme' }
]
</script>

<template>
  <Head title="Settings" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Settings
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage your account settings and preferences
          </p>
        </div>
        <div class="hidden md:flex items-center space-x-2">
          <ShieldCheckIcon class="w-5 h-5 text-green-500" />
          <span class="text-sm text-gray-600 dark:text-gray-400">All changes are saved securely</span>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
          <!-- Sidebar Navigation -->
          <aside class="lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden sticky top-24">
              <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                  Navigation
                </h3>
              </div>
              <nav class="p-2 space-y-1">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    'w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200',
                    activeTab === tab.id
                      ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 shadow-sm'
                      : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                  ]"
                >
                  <component :is="tab.icon" class="w-5 h-5 mr-3 flex-shrink-0" />
                  <div class="flex-1 text-left">
                    <div class="font-medium">{{ tab.name }}</div>
                    <div v-if="activeTab === tab.id" class="text-xs opacity-75 mt-0.5">{{ tab.description }}</div>
                  </div>
                  <CheckCircleIcon 
                    v-if="activeTab === tab.id" 
                    class="w-4 h-4 ml-2 text-blue-600 dark:text-blue-400 flex-shrink-0"
                  />
                </button>
              </nav>
            </div>
          </aside>

          <!-- Main Content -->
          <div class="lg:col-span-9 space-y-6 mt-6 lg:mt-0">
            
            <!-- Profile Settings -->
            <div v-show="activeTab === 'profile'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center">
                      <UserCircleIcon class="w-7 h-7 text-white" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Update your personal details and email address</p>
                  </div>
                </div>
              </div>
              
              <form @submit.prevent="updateProfile" class="p-6 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Full Name <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="profileForm.name"
                    @blur="validateProfile"
                    type="text"
                    :class="[
                      'w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors',
                      'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                      (profileErrors.name || profileForm.errors.name) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                    ]"
                    placeholder="Enter your full name"
                  >
                  <p v-if="profileErrors.name || profileForm.errors.name" class="mt-1.5 text-sm text-red-600 flex items-center">
                    <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                    {{ profileErrors.name || profileForm.errors.name }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email Address <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="profileForm.email"
                    @blur="validateProfile"
                    type="email"
                    :class="[
                      'w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors',
                      'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                      (profileErrors.email || profileForm.errors.email) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                    ]"
                    placeholder="your.email@example.com"
                  >
                  <p v-if="profileErrors.email || profileForm.errors.email" class="mt-1.5 text-sm text-red-600 flex items-center">
                    <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                    {{ profileErrors.email || profileForm.errors.email }}
                  </p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="text-red-500">*</span> Required fields
                  </p>
                  <button 
                    type="submit" 
                    :disabled="profileForm.processing"
                    class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md"
                  >
                    <CheckCircleIcon v-if="!profileForm.processing" class="w-4 h-4 mr-2" />
                    <svg v-else class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Security Settings -->
            <div v-show="activeTab === 'security'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-red-50 to-orange-50 dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-red-600 dark:bg-red-500 flex items-center justify-center">
                      <LockClosedIcon class="w-7 h-7 text-white" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ensure your account stays secure with a strong password</p>
                  </div>
                </div>
              </div>
              
              <form @submit.prevent="updatePassword" class="p-6 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Current Password <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      v-model="passwordForm.current_password"
                      :type="showCurrentPassword ? 'text' : 'password'"
                      :class="[
                        'w-full px-4 py-2.5 pr-12 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors',
                        'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        (passwordErrors.current_password || passwordForm.errors.current_password) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                      ]"
                      placeholder="Enter your current password"
                    >
                    <button
                      type="button"
                      @click="showCurrentPassword = !showCurrentPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      <EyeIcon v-if="!showCurrentPassword" class="w-5 h-5" />
                      <EyeSlashIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                  <p v-if="passwordErrors.current_password || passwordForm.errors.current_password" class="mt-1.5 text-sm text-red-600 flex items-center">
                    <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                    {{ passwordErrors.current_password || passwordForm.errors.current_password }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    New Password <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      v-model="passwordForm.password"
                      @input="validatePassword"
                      :type="showNewPassword ? 'text' : 'password'"
                      :class="[
                        'w-full px-4 py-2.5 pr-12 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors',
                        'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        (passwordErrors.password || passwordForm.errors.password) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                      ]"
                      placeholder="Enter a strong password"
                    >
                    <button
                      type="button"
                      @click="showNewPassword = !showNewPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      <EyeIcon v-if="!showNewPassword" class="w-5 h-5" />
                      <EyeSlashIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                  
                  <!-- Password Strength Indicator -->
                  <div v-if="passwordForm.password" class="mt-2">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Password Strength:</span>
                      <span :class="['text-xs font-semibold', passwordStrength.strength >= 3 ? 'text-green-600' : 'text-orange-600']">
                        {{ passwordStrength.label }}
                      </span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div 
                        :class="[passwordStrength.color, 'h-full transition-all duration-300']"
                        :style="{ width: `${(passwordStrength.strength / 5) * 100}%` }"
                      ></div>
                    </div>
                  </div>
                  
                  <p v-if="passwordErrors.password || passwordForm.errors.password" class="mt-1.5 text-sm text-red-600 flex items-center">
                    <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                    {{ passwordErrors.password || passwordForm.errors.password }}
                  </p>
                  <p v-else class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    Use 8+ characters with a mix of letters, numbers & symbols
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Confirm New Password <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      v-model="passwordForm.password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      :class="[
                        'w-full px-4 py-2.5 pr-12 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors',
                        'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        passwordErrors.password_confirmation ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                      ]"
                      placeholder="Confirm your new password"
                    >
                    <button
                      type="button"
                      @click="showConfirmPassword = !showConfirmPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      <EyeIcon v-if="!showConfirmPassword" class="w-5 h-5" />
                      <EyeSlashIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                  <p v-if="passwordErrors.password_confirmation" class="mt-1.5 text-sm text-red-600 flex items-center">
                    <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                    {{ passwordErrors.password_confirmation }}
                  </p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="text-red-500">*</span> All fields required
                  </p>
                  <button 
                    type="submit" 
                    :disabled="passwordForm.processing"
                    class="inline-flex items-center px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md"
                  >
                    <LockClosedIcon v-if="!passwordForm.processing" class="w-4 h-4 mr-2" />
                    <svg v-else class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Notifications Settings -->
            <div v-show="activeTab === 'notifications'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-50 to-teal-50 dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-green-600 dark:bg-green-500 flex items-center justify-center">
                      <BellIcon class="w-7 h-7 text-white" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Preferences</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Choose how you want to receive notifications</p>
                  </div>
                </div>
              </div>
              
              <div class="p-6 space-y-6">
                <!-- Email Notifications -->
                <div class="flex items-start justify-between py-4">
                  <div class="flex-1">
                    <div class="flex items-center">
                      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Email Notifications</h3>
                      <span :class="['ml-2 px-2 py-0.5 text-xs font-medium rounded-full', emailNotifications ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400']">
                        {{ emailNotifications ? 'Enabled' : 'Disabled' }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Receive email alerts for important security updates and system notifications
                    </p>
                  </div>
                  <button 
                    @click="toggleEmailNotifications" 
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                      emailNotifications ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'
                    ]"
                  >
                    <span 
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        emailNotifications ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Push Notifications -->
                <div class="flex items-start justify-between py-4">
                  <div class="flex-1">
                    <div class="flex items-center">
                      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Push Notifications</h3>
                      <span :class="['ml-2 px-2 py-0.5 text-xs font-medium rounded-full', pushNotifications ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400']">
                        {{ pushNotifications ? 'Enabled' : 'Disabled' }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Get instant browser notifications for critical alerts and events
                    </p>
                  </div>
                  <button 
                    @click="togglePushNotifications" 
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                      pushNotifications ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'
                    ]"
                  >
                    <span 
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        pushNotifications ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Security Alerts -->
                <div class="flex items-start justify-between py-4">
                  <div class="flex-1">
                    <div class="flex items-center">
                      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Security Alerts</h3>
                      <span :class="['ml-2 px-2 py-0.5 text-xs font-medium rounded-full', securityAlerts ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400']">
                        {{ securityAlerts ? 'Enabled' : 'Disabled' }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Receive notifications when new security threats are detected
                    </p>
                  </div>
                  <button 
                    @click="toggleSecurityAlerts" 
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                      securityAlerts ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'
                    ]"
                  >
                    <span 
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        securityAlerts ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </div>

            <!-- Appearance Settings -->
            <div v-show="activeTab === 'appearance'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-purple-600 dark:bg-purple-500 flex items-center justify-center">
                      <PaintBrushIcon class="w-7 h-7 text-white" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Appearance Settings</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Customize how the application looks</p>
                  </div>
                </div>
              </div>
              
              <div class="p-6">
                <div class="flex items-start justify-between py-4">
                  <div class="flex-1">
                    <div class="flex items-center">
                      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Dark Mode</h3>
                      <span :class="['ml-2 px-2 py-0.5 text-xs font-medium rounded-full', darkMode ? 'bg-gray-800 text-white dark:bg-gray-700' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400']">
                        {{ darkMode ? 'Dark' : 'Light' }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Toggle between light and dark theme for comfortable viewing
                    </p>
                  </div>
                  <button 
                    @click="toggleDarkMode" 
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                      darkMode ? 'bg-gray-800 dark:bg-gray-700' : 'bg-yellow-400'
                    ]"
                  >
                    <span 
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        darkMode ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

