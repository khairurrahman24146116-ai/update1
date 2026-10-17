<template>
  <div ref="el" class="ambient-cursor" aria-hidden="true"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  size: { type: Number, default: 440 },
  ease: { type: Number, default: 0.14 },
})

const el = ref(null)
let raf = null
let running = false
let targetX = -9999
let targetY = -9999
let currentX = -9999
let currentY = -9999
let visible = false

function setVisible(next) {
  if (visible === next) return
  visible = next
  el.value?.classList.toggle('is-visible', next)
}

function frame() {
  currentX += (targetX - currentX) * props.ease
  currentY += (targetY - currentY) * props.ease

  if (el.value) {
    el.value.style.transform = `translate3d(${currentX}px, ${currentY}px, 0)`
  }

  const settled = Math.abs(targetX - currentX) < 0.4 && Math.abs(targetY - currentY) < 0.4
  if (settled) {
    running = false
    return
  }
  raf = requestAnimationFrame(frame)
}

function onPointerMove(event) {
  if (event.pointerType === 'touch') return
  targetX = event.clientX
  targetY = event.clientY
  if (!visible) {
    currentX = targetX
    currentY = targetY
    setVisible(true)
  }
  if (!running) {
    running = true
    raf = requestAnimationFrame(frame)
  }
}

function onPointerLeave() {
  setVisible(false)
}

function onPointerOut(event) {
  if (!event.relatedTarget) {
    setVisible(false)
  }
}

onMounted(() => {
  const fine = window.matchMedia?.('(pointer: fine)').matches
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (!fine || reduce) return

  el.value?.style.setProperty('--glow-size', `${props.size}px`)
  window.addEventListener('pointermove', onPointerMove, { passive: true })
  window.addEventListener('pointerout', onPointerOut, { passive: true })
  document.addEventListener('mouseleave', onPointerLeave)
})

onBeforeUnmount(() => {
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerout', onPointerOut)
  document.removeEventListener('mouseleave', onPointerLeave)
  if (raf != null) cancelAnimationFrame(raf)
  running = false
})
</script>
