<template>
  <div ref="el" class="scroll-progress" aria-hidden="true"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const el = ref(null)
let raf = null

function update() {
  raf = null
  const doc = document.documentElement
  const max = doc.scrollHeight - doc.clientHeight
  const ratio = max > 0 ? Math.min(1, Math.max(0, doc.scrollTop / max)) : 0
  if (el.value) {
    el.value.style.setProperty('--scroll-progress', ratio.toFixed(4))
    el.value.classList.toggle('is-visible', doc.scrollTop > 8)
  }
}

function onScroll() {
  if (raf == null) raf = requestAnimationFrame(update)
}

onMounted(() => {
  update()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  if (raf != null) cancelAnimationFrame(raf)
})
</script>
