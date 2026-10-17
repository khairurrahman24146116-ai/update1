<template>
  <button
    type="button"
    class="control-glass theme-toggle"
    :aria-label="isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
    :title="isDark ? 'Mode terang' : 'Mode gelap'"
    :aria-pressed="isDark"
    @click="toggle"
  >
    <span class="material-symbols-outlined">{{ isDark ? 'light_mode' : 'dark_mode' }}</span>
  </button>
</template>

<script setup>
import { useTheme } from '../composables/useTheme.js'

const { isDark, toggle } = useTheme()
</script>

<style scoped>
.theme-toggle {
  width: 36px;
  height: 36px;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-on-surface);
  cursor: pointer;
  flex-shrink: 0;
}
.theme-toggle .material-symbols-outlined {
  font-size: 20px;
  width: 20px;
  height: 20px;
}
</style>
