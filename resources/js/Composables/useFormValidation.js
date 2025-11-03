import { ref, reactive, computed } from 'vue'

export function useFormValidation(initialData = {}) {
  const formData = reactive({ ...initialData })
  const errors = reactive({})
  const isSubmitting = ref(false)
  const isValid = ref(false)

  // Validation rules
  const rules = {
    required: (value) => {
      if (typeof value === 'string') return value.trim().length > 0
      if (Array.isArray(value)) return value.length > 0
      return value !== null && value !== undefined
    },
    email: (value) => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(value)
    },
    minLength: (min) => (value) => {
      if (typeof value === 'string') return value.length >= min
      return true
    },
    maxLength: (max) => (value) => {
      if (typeof value === 'string') return value.length <= max
      return true
    },
    numeric: (value) => !isNaN(parseFloat(value)) && isFinite(value),
    ip: (value) => {
      const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
      return ipRegex.test(value)
    },
    between: (min, max) => (value) => {
      const num = parseFloat(value)
      return num >= min && num <= max
    },
    in: (options) => (value) => options.includes(value)
  }

  // Custom validation messages
  const messages = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    minLength: (min) => `Must be at least ${min} characters`,
    maxLength: (max) => `Must not exceed ${max} characters`,
    numeric: 'Must be a valid number',
    ip: 'Must be a valid IP address',
    between: (min, max) => `Must be between ${min} and ${max}`,
    in: (options) => `Must be one of: ${options.join(', ')}`
  }

  const validateField = (fieldName, value, fieldRules) => {
    const fieldErrors = []
    
    for (const rule of fieldRules) {
      let ruleName, ruleValue
      
      if (typeof rule === 'string') {
        ruleName = rule
        ruleValue = null
      } else if (Array.isArray(rule)) {
        [ruleName, ...ruleValue] = rule
      } else if (typeof rule === 'object') {
        ruleName = rule.name
        ruleValue = rule.value
      }
      
      if (rules[ruleName]) {
        let validator = rules[ruleName]
        if (ruleValue !== null && ruleValue !== undefined) {
          if (Array.isArray(ruleValue)) {
            validator = validator(...ruleValue)
          } else {
            validator = validator(ruleValue)
          }
        }
        const isValid = validator(value)
        if (!isValid) {
          let message = messages[ruleName]
          if (typeof message === 'function') {
            message = Array.isArray(ruleValue) ? message(...ruleValue) : message(ruleValue)
          }
          fieldErrors.push(message)
        }
      }
    }
    
    if (fieldErrors.length > 0) {
      errors[fieldName] = fieldErrors[0] // Show first error
    } else {
      delete errors[fieldName]
    }
  }

  const validateForm = (validationSchema) => {
    let hasErrors = false
    
    for (const [fieldName, fieldRules] of Object.entries(validationSchema)) {
      const value = formData[fieldName]
      validateField(fieldName, value, fieldRules)
      
      if (errors[fieldName]) {
        hasErrors = true
      }
    }
    
    isValid.value = !hasErrors
    return !hasErrors
  }

  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key])
  }

  const setError = (field, message) => {
    errors[field] = message
  }

  const hasError = (field) => {
    return !!errors[field]
  }

  const getError = (field) => {
    return errors[field] || ''
  }

  const reset = () => {
    Object.keys(formData).forEach(key => {
      if (Array.isArray(initialData[key])) {
        formData[key] = []
      } else if (typeof initialData[key] === 'object' && initialData[key] !== null) {
        formData[key] = {}
      } else {
        formData[key] = initialData[key] || ''
      }
    })
    clearErrors()
    isSubmitting.value = false
    isValid.value = false
  }

  return {
    formData,
    errors,
    isSubmitting,
    isValid,
    validateField,
    validateForm,
    clearErrors,
    setError,
    hasError,
    getError,
    reset
  }
}
