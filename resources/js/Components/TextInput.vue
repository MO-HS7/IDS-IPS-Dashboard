<template>
    <input
        :id="id"
        ref="input"
        v-model="model"
        :type="type"
        :class="[
            'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm',
            inputClass
        ]"
        :placeholder="placeholder"
        :required="required"
        :autocomplete="autocomplete"
    />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    id: String,
    modelValue: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    placeholder: String,
    required: Boolean,
    autocomplete: String,
    class: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const input = ref(null)

const model = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})

const inputClass = computed(() => props.class)

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus()
    }
})

defineExpose({ focus: () => input.value?.focus() })
</script>