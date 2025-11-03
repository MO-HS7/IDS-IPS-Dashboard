<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()
const page = usePage()

// Handle flash messages from backend
const checkFlashMessages = () => {
  if (page.props.flash?.success) {
    success('Success', page.props.flash.success)
  }
  if (page.props.flash?.error) {
    error('Error', page.props.flash.error)
  }
}

// Check flash messages on mount and when page props change
onMounted(() => {
  checkFlashMessages()
})

watch(() => page.props.flash, () => {
  checkFlashMessages()
}, { deep: true })

const props = defineProps({
  users: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// State
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedUser = ref(null)
const searchQuery = ref(props.filters.search || '')
const filterRole = ref(props.filters.role || '')
const filterStatus = ref(props.filters.status || '')

// Form
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  status: 'active'
})

const currentUserId = computed(() => page.props.auth?.user?.id)

// Apply filters via Inertia (server-side)
const applyFilters = () => {
  router.get('/users', {
    search: searchQuery.value || undefined,
    role: filterRole.value || undefined,
    status: filterStatus.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Debounced search
let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const openCreateModal = () => {
  isEditing.value = false
  form.reset()
  form.clearErrors()
  showModal.value = true
}

const editUser = (user) => {
  isEditing.value = true
  selectedUser.value = user
  form.name = user.name
  form.email = user.email
  form.role = user.role
  form.status = user.status
  form.password = ''
  form.password_confirmation = ''
  showViewModal.value = false
  showModal.value = true
}

const viewUser = (user) => {
  selectedUser.value = user
  showViewModal.value = true
}

const closeModal = () => {
  showModal.value = false
  form.reset()
  form.clearErrors()
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedUser.value = null
}

const submitForm = () => {
  if (!form.name || !form.email || !form.role) {
    error('Validation Error', 'Please fill in all required fields')
    return
  }
  
  if (!isEditing.value && (!form.password || form.password.length < 8)) {
    error('Validation Error', 'Password must be at least 8 characters')
    return
  }
  
  if (!isEditing.value && form.password !== form.password_confirmation) {
    error('Validation Error', 'Passwords do not match')
    return
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.email)) {
    error('Validation Error', 'Please enter a valid email address')
    return
  }
  
  if (isEditing.value) {
    form.put(`/users/${selectedUser.value.id}`, {
      onSuccess: () => {
        // Flash message will trigger toast via watch
        closeModal()
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0]
        if (firstError) {
          error('Error', firstError)
        }
      }
    })
  } else {
    form.post('/users', {
      onSuccess: () => {
        // Flash message will trigger toast via watch
        closeModal()
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0]
        if (firstError) {
          error('Error', firstError)
        }
      }
    })
  }
}

const confirmDelete = (user) => {
  if (user.id === currentUserId.value) {
    error('Error', 'You cannot delete your own account!')
    return
  }
  
  if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
    router.delete(`/users/${user.id}`, {
      onSuccess: () => {
        // Flash message will trigger toast via watch
      },
      onError: () => {
        error('Error', 'Failed to delete user')
      }
    })
  }
}

const getRoleBadgeClass = (role) => {
  const classes = {
    'Admin': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    'Analyst': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    'Viewer': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
  }
  return classes[role] || classes.Viewer
}

const getStatusBadgeClass = (status) => {
  return status === 'active'
    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}
</script>

<template>
  <Head title="User Management" />

  <AuthenticatedLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Manage system users, roles, and permissions
            </p>
          </div>
          <button
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add New User
          </button>
        </div>

        <!-- Filters & Search -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
          <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  @input="handleSearch"
                  type="text"
                  placeholder="Search by name or email..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
            <div class="w-full md:w-48">
              <select
                v-model="filterRole"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Roles</option>
                <option value="Admin">Admin</option>
                <option value="Analyst">Analyst</option>
                <option value="Viewer">Viewer</option>
              </select>
            </div>
            <div class="w-full md:w-48">
              <select
                v-model="filterStatus"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
          <div v-if="!users.data || users.data.length === 0" class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-.5a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No users found</h3>
            <p class="text-gray-500 dark:text-gray-400">
              {{ searchQuery ? 'Try adjusting your search or filters' : 'Get started by adding your first user' }}
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">#{{ user.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-white">{{ user.name.charAt(0).toUpperCase() }}</span>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ user.email }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getRoleBadgeClass(user.role)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">{{ user.role }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusBadgeClass(user.status)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">{{ user.status === 'active' ? 'Active' : 'Inactive' }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(user.created_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                      <button @click="viewUser(user)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20" title="View Details">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      </button>
                      <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 p-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/20" title="Edit User">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button v-if="user.id !== currentUserId" @click="confirmDelete(user)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete User">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="users.total > users.per_page" class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing <span class="font-medium">{{ users.from }}</span> to <span class="font-medium">{{ users.to }}</span> of <span class="font-medium">{{ users.total }}</span> users
              </div>
              <div class="flex space-x-2">
                <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Previous</Link>
                <Link v-if="users.next_page_url" :href="users.next_page_url" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Next</Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <Modal :show="showModal" @close="closeModal" max-width="2xl">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ isEditing ? 'Edit User' : 'Create New User' }}</h3>
        <form @submit.prevent="submitForm">
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name <span class="text-red-500">*</span></label>
            <input id="name" v-model="form.name" type="text" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.name }">
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address <span class="text-red-500">*</span></label>
            <input id="email" v-model="form.email" type="email" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.email }">
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>
          <div v-if="!isEditing" class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password <span class="text-red-500">*</span></label>
            <input id="password" v-model="form.password" type="password" :required="!isEditing" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.password }">
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum 8 characters</p>
          </div>
          <div v-if="!isEditing" class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm Password <span class="text-red-500">*</span></label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" :required="!isEditing" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role <span class="text-red-500">*</span></label>
            <select id="role" v-model="form.role" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.role }">
              <option value="">Select a role</option>
              <option value="Admin">Admin</option>
              <option value="Analyst">Analyst</option>
              <option value="Viewer">Viewer</option>
            </select>
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
          </div>
          <div class="mb-6">
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status <span class="text-red-500">*</span></label>
            <select id="status" v-model="form.status" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="flex items-center justify-end space-x-3">
            <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="form.processing">{{ isEditing ? 'Updating...' : 'Creating...' }}</span>
              <span v-else>{{ isEditing ? 'Update User' : 'Create User' }}</span>
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- View User Modal -->
    <Modal :show="showViewModal" @close="closeViewModal" max-width="2xl">
      <div class="p-6" v-if="selectedUser">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">User Details</h3>
        <div class="space-y-4">
          <div class="flex items-center space-x-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
              <span class="text-2xl font-semibold text-white">{{ selectedUser.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <h4 class="text-xl font-semibold text-gray-900 dark:text-white">{{ selectedUser.name }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedUser.email }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User ID</p>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">#{{ selectedUser.id }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</p>
              <p class="mt-1"><span :class="getRoleBadgeClass(selectedUser.role)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">{{ selectedUser.role }}</span></p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
              <p class="mt-1"><span :class="getStatusBadgeClass(selectedUser.status)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">{{ selectedUser.status === 'active' ? 'Active' : 'Inactive' }}</span></p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified</p>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ selectedUser.email_verified_at ? 'Yes' : 'No' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</p>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUser.created_at) }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</p>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUser.updated_at) }}</p>
            </div>
          </div>
        </div>
        <div class="mt-6 flex items-center justify-end space-x-3">
          <button @click="closeViewModal" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Close</button>
          <button @click="editUser(selectedUser)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors">Edit User</button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
