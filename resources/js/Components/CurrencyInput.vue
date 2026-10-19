<template>
  <input
    ref="el"
    type="text"
    inputmode="numeric"
    autocomplete="off"
    v-bind="$attrs"
    @input="onInput"
    @blur="onBlur"
  />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { formatDigits, parseDigits } from '../composables/useFinance.js'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
})
const emit = defineEmits(['update:modelValue'])

const el = ref(null)

onMounted(() => {
  if (el.value) el.value.value = formatDigits(props.modelValue)
})

watch(
  () => props.modelValue,
  (value) => {
    const formatted = formatDigits(value)
    if (el.value && el.value.value !== formatted) el.value.value = formatted
  },
)

function caretFromDigits(formatted, digitsBefore) {
  if (digitsBefore <= 0) return 0
  let seen = 0
  for (let i = 0; i < formatted.length; i++) {
    if (/\d/.test(formatted[i])) seen++
    if (seen === digitsBefore) return i + 1
  }
  return formatted.length
}

function onInput(event) {
  const input = event.target
  const caret = input.selectionStart ?? input.value.length
  const digitsBefore = parseDigits(input.value.slice(0, caret)).length
  const raw = parseDigits(input.value)
  const formatted = formatDigits(raw)

  input.value = formatted
  emit('update:modelValue', raw)
  const pos = caretFromDigits(formatted, digitsBefore)
  input.setSelectionRange(pos, pos)
}

function onBlur() {
  if (el.value) el.value.value = formatDigits(props.modelValue)
}
</script>
