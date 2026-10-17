<template>
  <div ref="el" class="interactive-card" v-bind="$attrs">
    <slot />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

defineOptions({ inheritAttrs: false })

const el = ref(null)
let raf = null
let px = 0
let py = 0

function apply() {
  raf = null
  if (!el.value) return
  el.value.style.setProperty('--mx', `${px}px`)
  el.value.style.setProperty('--my', `${py}px`)
}

function onPointerMove(event) {
  if (event.pointerType === 'touch') return
  const rect = el.value.getBoundingClientRect()
  px = event.clientX - rect.left
  py = event.clientY - rect.top
  if (raf == null) raf = requestAnimationFrame(apply)
}

onMounted(() => {
  el.value?.addEventListener('pointermove', onPointerMove, { passive: true })
})

onBeforeUnmount(() => {
  el.value?.removeEventListener('pointermove', onPointerMove)
  if (raf != null) cancelAnimationFrame(raf)
})
</script>
