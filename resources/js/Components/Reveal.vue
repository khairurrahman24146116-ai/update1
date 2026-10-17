<template>
  <component :is="as" ref="el" class="reveal" :class="[`reveal--${variant}`, { 'reveal--slow': slow }]" :style="{ '--reveal-delay': `${delay}ms` }">
    <slot />
  </component>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'

defineOptions({ inheritAttrs: true })

const props = defineProps({
  delay: { type: Number, default: 0 },
  slow: { type: Boolean, default: false },
  as: { type: String, default: 'div' },
  variant: {
    type: String,
    default: 'up',
    validator: (v) => ['up', 'in', 'left', 'right', 'scale'].includes(v),
  },
})

const el = ref(null)
let observer = null

onMounted(() => {
  if (!('IntersectionObserver' in window)) {
    el.value?.classList.add('reveal-visible', 'is-visible')
    return
  }
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-visible', 'is-visible')
          observer.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.12, rootMargin: '0px 0px -32px 0px' }
  )
  if (el.value) observer.observe(el.value)
})

onUnmounted(() => {
  if (observer && el.value) observer.unobserve(el.value)
})
</script>

<style scoped>
.reveal,
.reveal-visible {
  transition:
    opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
    transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: var(--reveal-delay, 0ms);
  will-change: opacity, transform;
}

.reveal {
  opacity: 0;
}

.reveal--up { transform: translateY(22px) scale(0.985); }
.reveal--in { transform: none; }
.reveal--left { transform: translateX(-28px); }
.reveal--right { transform: translateX(28px); }
.reveal--scale { transform: scale(0.94); }

.reveal--slow {
  transition-duration: 1.1s;
}

.reveal-visible {
  opacity: 1;
  transform: translateY(0) translateX(0) scale(1);
}

@media (prefers-reduced-motion: reduce) {
  .reveal {
    opacity: 1;
    transform: none;
    transition: none;
  }
}
</style>
