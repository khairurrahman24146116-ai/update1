<template>
  <span class="logo-plate" :style="plateStyle">
    <img
      :src="currentSrc"
      :alt="alt"
      :width="size"
      :height="size"
      class="h-full w-full object-contain"
      loading="lazy"
      @error="currentSrc = fallback"
    />
  </span>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  src: { type: String, default: '' },
  alt: { type: String, default: 'Logo LPI Dayah Madani Al-Aziziyah' },
  size: { type: [Number, String], default: 40 },
  shape: { type: String, default: 'lg' },
})

const fallback = '/images/profile/logo-lpi-transparent.png'
const currentSrc = ref(props.src || fallback)

watch(
  () => props.src,
  (val) => {
    currentSrc.value = val || fallback
  }
)

const plateStyle = computed(() => {
  const size = Number(props.size) || 40
  const radius = props.shape === 'pill'
    ? '9999px'
    : `${Math.max(6, Math.min(22, Math.round(size * 0.28)))}px`
  return {
    width: `${size}px`,
    height: `${size}px`,
    borderRadius: radius,
    padding: `${Math.max(2, Math.round(size * 0.08))}px`,
  }
})
</script>