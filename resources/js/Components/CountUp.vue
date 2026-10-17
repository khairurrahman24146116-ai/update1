<template>
  <span ref="el">{{ display }}</span>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  value: { type: [String, Number], required: true },
  duration: { type: Number, default: 1100 },
})

const raw = String(props.value)
const match = raw.match(/^(\D*?)(\d[\d.,]*)(.*)$/)
const prefix = match ? match[1] : ''
const numeric = match ? Number(match[2].replace(/[.,](?=\d{3}\b)/g, '').replace(',', '.')) : 0
const suffix = match ? match[3] : ''

const display = ref(match ? `${prefix}0${suffix}` : raw)
const el = ref(null)
let observer = null
let raf = null

function finish() {
  display.value = raw
}

function animate() {
  const start = performance.now()
  const tick = (now) => {
    const progress = Math.min(1, (now - start) / props.duration)
    const eased = 1 - Math.pow(1 - progress, 3)
    const current = Math.round(numeric * eased)
    display.value = `${prefix}${current}${suffix}`
    if (progress < 1) {
      raf = requestAnimationFrame(tick)
    } else {
      finish()
    }
  }
  raf = requestAnimationFrame(tick)
}

onMounted(() => {
  if (!match || Number.isNaN(numeric)) {
    finish()
    return
  }
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (reduce || !('IntersectionObserver' in window)) {
    finish()
    return
  }
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          observer.unobserve(entry.target)
          animate()
        }
      })
    },
    { threshold: 0.4 }
  )
  if (el.value) observer.observe(el.value)
})

onBeforeUnmount(() => {
  if (observer && el.value) observer.unobserve(el.value)
  if (raf != null) cancelAnimationFrame(raf)
})
</script>
