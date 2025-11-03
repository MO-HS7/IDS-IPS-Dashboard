import { ref, reactive } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  const addToast = (toast) => {
    const id = ++toastId
    const toastData = {
      id,
      type: toast.type || 'info',
      title: toast.title || '',
      message: toast.message || '',
      duration: toast.duration || 5000,
      persistent: toast.persistent || false
    }
    
    toasts.value.push(toastData)
    
    return id
  }

  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const success = (title, message = '', options = {}) => {
    return addToast({
      type: 'success',
      title,
      message,
      ...options
    })
  }

  const error = (title, message = '', options = {}) => {
    return addToast({
      type: 'error',
      title,
      message,
      duration: 8000, // Longer duration for errors
      ...options
    })
  }

  const warning = (title, message = '', options = {}) => {
    return addToast({
      type: 'warning',
      title,
      message,
      ...options
    })
  }

  const info = (title, message = '', options = {}) => {
    return addToast({
      type: 'info',
      title,
      message,
      ...options
    })
  }

  const clearAll = () => {
    toasts.value = []
  }

  return {
    toasts, // Return the reactive ref, not the value
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
    clearAll
  }
}
