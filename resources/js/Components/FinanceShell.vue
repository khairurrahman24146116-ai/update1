<template>
  <AppShell :title="title">
    <FinanceNav :show-period="showPeriod" :from="from" :to="to" @update:from="$emit('update:from', $event)" @update:to="$emit('update:to', $event)" @range="$emit('range', $event)" />
    <slot />
  </AppShell>
</template>

<script setup>
import AppShell from './AppShell.vue'
import FinanceNav from './FinanceNav.vue'

defineProps({
  title: { type: String, default: 'Keuangan' },
  showPeriod: { type: Boolean, default: false },
  from: { type: String, default: '' },
  to: { type: String, default: '' },
})
defineEmits(['update:from', 'update:to', 'range'])
</script>
