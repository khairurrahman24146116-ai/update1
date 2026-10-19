<template>
  <section class="structure-board w-full max-w-7xl mx-auto px-gutter pb-space-lg">
    <div class="flex flex-wrap items-center gap-2 pb-space-sm">
      <span class="inline-flex items-center gap-1.5 bg-primary text-on-primary px-3 py-1 rounded-full font-label-sm text-label-sm font-semibold uppercase tracking-wider">
        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">account_tree</span>
        {{ badge }}
      </span>
    </div>

    <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface tracking-tight">{{ title }}</h1>
    <p v-if="description" class="font-body-md text-body-md text-on-surface-variant mt-1 max-w-3xl">{{ description }}</p>

    <div v-if="loading" class="mt-space-md glass-card rounded-[28px] p-space-xl flex flex-col items-center gap-space-sm">
      <span class="material-symbols-outlined text-primary text-[36px] animate-spin">progress_activity</span>
      <p class="font-body-md text-body-md text-on-surface-variant">Memuat struktur organisasi…</p>
    </div>

    <div v-else-if="error" class="mt-space-md rounded-[28px] border border-error bg-error-container shadow-sm p-space-lg flex flex-col items-center text-center gap-space-xs">
      <span class="material-symbols-outlined text-error text-[34px]">error</span>
      <p class="font-body-md text-body-md text-on-error-container">{{ error }}</p>
      <button type="button" class="px-5 h-10 rounded-full bg-primary text-on-primary font-title-md text-title-md" @click="load">Coba Lagi</button>
    </div>

    <div v-else-if="!tree.length" class="mt-space-md glass-card rounded-[28px] p-space-xl flex flex-col items-center text-center gap-space-xs">
      <span class="material-symbols-outlined text-primary text-[40px]">account_tree</span>
      <h2 class="font-headline-md text-headline-md font-semibold">{{ emptyText }}</h2>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-md">
        Data struktur organisasi unit ini belum tersedia.
      </p>
    </div>

    <div v-else class="structure-shell mt-space-md rounded-[28px] border border-outline-variant bg-surface-container-lowest shadow-sm p-space-md md:p-space-lg">
      <div class="structure-toolbar">
        <p class="structure-toolbar__hint">
          <span class="material-symbols-outlined" aria-hidden="true">pan_tool</span>
          Geser bagan, cubit atau scroll untuk zoom
        </p>
        <div class="structure-controls">
          <div class="structure-zoom" role="group" aria-label="Kontrol zoom bagan">
            <button type="button" class="structure-zoom__btn" :disabled="zoom <= minZoom" aria-label="Perkecil bagan" @click="step(-0.1)">
              <span class="material-symbols-outlined" aria-hidden="true">remove</span>
            </button>
            <button type="button" class="structure-zoom__value" aria-label="Kembali ke skala 100 persen" title="Klik untuk skala 100%" @click="zoomToHundred">
              {{ zoomPercent }}%
            </button>
            <button type="button" class="structure-zoom__btn" :disabled="zoom >= maxZoom" aria-label="Perbesar bagan" @click="step(0.1)">
              <span class="material-symbols-outlined" aria-hidden="true">add</span>
            </button>
          </div>
          <button type="button" class="structure-fit" aria-label="Sesuaikan bagan agar seluruhnya muat di layar" title="Fit / reset tampilan" @click="fitToViewport">
            <span class="material-symbols-outlined" aria-hidden="true">fit_screen</span>
            <span>Fit</span>
          </button>
        </div>
      </div>

      <div
        ref="viewportEl"
        class="structure-viewport"
        :class="{ 'is-panning': isPanning }"
        role="group"
        aria-label="Kanvas bagan struktur organisasi yang dapat digeser dan diperbesar"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @lostpointercapture="onPointerUp"
        @dragstart.prevent
      >
        <div ref="stageEl" class="structure-stage" :class="{ 'is-ready': stageReady }">
          <ul class="structure-tree">
            <StructureNode v-for="root in tree" :key="root.id" :node="root" />
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import StructureNode from './StructureNode.vue'

const props = defineProps({
  unit: { type: String, required: true },
  title: { type: String, required: true },
  badge: { type: String, required: true },
  description: { type: String, default: '' },
  emptyText: { type: String, default: 'Belum diisi' },
})

const tree = ref([])
const loading = ref(true)
const error = ref('')

const viewportEl = ref(null)
const stageEl = ref(null)
const stageReady = ref(false)
const isPanning = ref(false)
const zoom = ref(1)

const zoomPercent = computed(() => Math.round(zoom.value * 100))

const MIN_ZOOM = 0.1
const MAX_ZOOM = 2
const ZOOM_STEP = 0.1
const FIT_PADDING = 28
const EDGE_MARGIN = 72

const view = { x: 0, y: 0, scale: 1 }
const stageSize = { width: 0, height: 0 }

let rafId = 0
let resizeTimer = 0
let hasUserAdjusted = false
const activePointers = new Map()
let panPoint = null
let pinchState = null

function clampScale(value) {
  return Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, value))
}

function scheduleApply() {
  if (rafId) {
    return
  }

  rafId = requestAnimationFrame(() => {
    rafId = 0
    if (!stageEl.value) {
      return
    }

    stageEl.value.style.transform = `translate3d(${view.x}px, ${view.y}px, 0) scale(${view.scale})`
    zoom.value = view.scale
  })
}

function measureStage() {
  if (!stageEl.value) {
    return false
  }

  stageSize.width = stageEl.value.offsetWidth
  stageSize.height = stageEl.value.offsetHeight

  return stageSize.width > 0 && stageSize.height > 0
}

function clampViewToViewport() {
  const viewport = viewportEl.value
  if (!viewport) {
    return
  }

  const width = viewport.clientWidth
  const height = viewport.clientHeight
  const contentWidth = stageSize.width * view.scale
  const contentHeight = stageSize.height * view.scale
  const marginX = Math.min(EDGE_MARGIN, Math.max(contentWidth, 1))
  const marginY = Math.min(EDGE_MARGIN, Math.max(contentHeight, 1))

  view.x = Math.min(width - marginX, Math.max(marginX - contentWidth, view.x))
  view.y = Math.min(height - marginY, Math.max(marginY - contentHeight, view.y))
}

function zoomAtPoint(nextScale, pointX, pointY) {
  const scale = clampScale(nextScale)

  if (!viewportEl.value) {
    view.scale = scale
    scheduleApply()
    return
  }

  const worldX = (pointX - view.x) / view.scale
  const worldY = (pointY - view.y) / view.scale

  view.x = pointX - worldX * scale
  view.y = pointY - worldY * scale
  view.scale = scale

  clampViewToViewport()
  scheduleApply()
}

function zoomAtCenter(nextScale) {
  const viewport = viewportEl.value
  if (!viewport) {
    return
  }

  zoomAtPoint(nextScale, viewport.clientWidth / 2, viewport.clientHeight / 2)
}

function step(delta) {
  hasUserAdjusted = true
  zoomAtCenter(view.scale + delta)
}

function zoomToHundred() {
  hasUserAdjusted = true
  zoomAtCenter(1)
}

function fitToViewport() {
  const viewport = viewportEl.value
  if (!viewport || !measureStage()) {
    return
  }

  const width = viewport.clientWidth
  const height = viewport.clientHeight
  const availableWidth = Math.max(80, width - FIT_PADDING * 2)
  const availableHeight = Math.max(80, height - FIT_PADDING * 2)
  const scale = clampScale(Math.min(availableWidth / stageSize.width, availableHeight / stageSize.height))

  view.scale = scale
  view.x = (width - stageSize.width * scale) / 2
  view.y = (height - stageSize.height * scale) / 2
  hasUserAdjusted = false
  stageReady.value = true
  scheduleApply()
}

function startPinch() {
  const viewport = viewportEl.value
  const points = [...activePointers.values()]
  if (!viewport || points.length < 2) {
    return
  }

  const [first, second] = points
  const rect = viewport.getBoundingClientRect()

  pinchState = {
    distance: Math.hypot(first.x - second.x, first.y - second.y) || 1,
    midX: (first.x + second.x) / 2 - rect.left,
    midY: (first.y + second.y) / 2 - rect.top,
    scale: view.scale,
    x: view.x,
    y: view.y,
  }

  panPoint = null
  isPanning.value = false
}

function updatePinch() {
  const viewport = viewportEl.value
  const points = [...activePointers.values()]
  if (!viewport || !pinchState || points.length < 2) {
    return
  }

  const [first, second] = points
  const rect = viewport.getBoundingClientRect()
  const distance = Math.hypot(first.x - second.x, first.y - second.y) || 1
  const midX = (first.x + second.x) / 2 - rect.left
  const midY = (first.y + second.y) / 2 - rect.top
  const scale = clampScale(pinchState.scale * (distance / pinchState.distance))
  const worldX = (pinchState.midX - pinchState.x) / pinchState.scale
  const worldY = (pinchState.midY - pinchState.y) / pinchState.scale

  view.scale = scale
  view.x = midX - worldX * scale
  view.y = midY - worldY * scale

  clampViewToViewport()
  scheduleApply()
}

function onPointerDown(event) {
  if (event.pointerType === 'mouse' && event.button !== 0) {
    return
  }

  const viewport = viewportEl.value
  if (!viewport) {
    return
  }

  activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY })
  try {
    viewport.setPointerCapture(event.pointerId)
  } catch (e) {
    // Pointer capture is best-effort (older browsers).
  }

  if (activePointers.size === 1) {
    panPoint = { x: event.clientX, y: event.clientY }
    pinchState = null
    isPanning.value = true
    hasUserAdjusted = true
  } else if (activePointers.size === 2) {
    startPinch()
    hasUserAdjusted = true
  }
}

function onPointerMove(event) {
  const point = activePointers.get(event.pointerId)
  if (!point) {
    return
  }

  point.x = event.clientX
  point.y = event.clientY

  if (activePointers.size >= 2) {
    updatePinch()
    return
  }

  if (!panPoint) {
    return
  }

  view.x += event.clientX - panPoint.x
  view.y += event.clientY - panPoint.y
  panPoint = { x: event.clientX, y: event.clientY }

  clampViewToViewport()
  scheduleApply()
}

function onPointerUp(event) {
  const viewport = viewportEl.value
  activePointers.delete(event.pointerId)

  if (viewport) {
    try {
      if (viewport.hasPointerCapture(event.pointerId)) {
        viewport.releasePointerCapture(event.pointerId)
      }
    } catch (e) {
      // Ignore browsers that already released the pointer.
    }
  }

  if (activePointers.size === 0) {
    panPoint = null
    pinchState = null
    isPanning.value = false
    return
  }

  if (activePointers.size === 1) {
    const [remaining] = activePointers.values()
    panPoint = { x: remaining.x, y: remaining.y }
    pinchState = null
    isPanning.value = true
    return
  }

  startPinch()
}

function onWheel(event) {
  const viewport = viewportEl.value
  if (!viewport) {
    return
  }

  event.preventDefault()

  const rect = viewport.getBoundingClientRect()
  const pointX = event.clientX - rect.left
  const pointY = event.clientY - rect.top
  const delta = event.deltaMode === 1 ? event.deltaY * 16 : event.deltaY
  const factor = Math.exp(-delta * 0.0015)

  hasUserAdjusted = true
  zoomAtPoint(view.scale * factor, pointX, pointY)
}

function onWindowResize() {
  window.clearTimeout(resizeTimer)
  resizeTimer = window.setTimeout(() => {
    if (!stageEl.value) {
      return
    }

    measureStage()

    if (hasUserAdjusted) {
      clampViewToViewport()
      scheduleApply()
      return
    }

    fitToViewport()
  }, 140)
}

function normalizeTree(nodes) {
  if (!Array.isArray(nodes)) {
    return []
  }

  if (nodes.some((node) => Array.isArray(node?.children))) {
    return nodes
  }

  const byParent = new Map()
  const ids = new Set(nodes.map((node) => node.id))

  nodes.forEach((node) => {
    const parentId = node.parent_id && ids.has(node.parent_id) ? node.parent_id : null
    if (!byParent.has(parentId)) {
      byParent.set(parentId, [])
    }
    byParent.get(parentId).push(node)
  })

  const build = (parentId) => (byParent.get(parentId) || [])
    .slice()
    .sort((a, b) => ((a.sort_order ?? 0) - (b.sort_order ?? 0)) || (a.id - b.id))
    .map((node) => ({ ...node, children: build(node.id) }))

  return build(null)
}

async function load() {
  loading.value = true
  error.value = ''
  stageReady.value = false

  let hasData = false

  try {
    const res = await fetch(`/api/structure/${props.unit}`, { headers: { Accept: 'application/json' } })
    if (!res.ok) {
      throw new Error('Gagal memuat struktur organisasi.')
    }

    const json = await res.json()
    tree.value = normalizeTree(json.tree)
    hasData = tree.value.length > 0
  } catch (e) {
    error.value = e.message || 'Gagal memuat struktur organisasi.'
  } finally {
    loading.value = false
  }

  if (!hasData) {
    return
  }

  await nextTick()
  fitToViewport()

  if (document.fonts?.ready) {
    document.fonts.ready.then(() => {
      if (!hasUserAdjusted) {
        fitToViewport()
      }
    })
  }
}

watch(viewportEl, (element, previous) => {
  previous?.removeEventListener('wheel', onWheel)
  element?.addEventListener('wheel', onWheel, { passive: false })
})

onMounted(() => {
  window.addEventListener('resize', onWindowResize)
  load()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onWindowResize)
  window.clearTimeout(resizeTimer)
  viewportEl.value?.removeEventListener('wheel', onWheel)

  if (rafId) {
    cancelAnimationFrame(rafId)
  }
})
</script>

<style>
/*
 * Pohon organisasi memakai teknik nested <ul>/<li> + pseudo-element.
 * Semua ukuran kartu tetap, sehingga susunan di HP identik dengan desktop;
 * hanya skala kanvas yang berbeda.
 */
.structure-tree,
.structure-tree ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.structure-tree {
  --org-card-width: 140px;
  --org-gap-y: 28px;
  --org-gap-x: 10px;
  --org-line-width: 3px;
  --org-line: #111111;

  display: flex;
  align-items: flex-start;
  justify-content: center;
  width: max-content;
}

.structure-tree ul {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: var(--org-gap-y);
}

.structure-tree li {
  position: relative;
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  align-items: center;
  padding: var(--org-gap-y) var(--org-gap-x) 0;
}

.structure-tree li::before,
.structure-tree li::after {
  content: '';
  position: absolute;
  top: 0;
  width: 50%;
  height: var(--org-gap-y);
  border-top: var(--org-line-width) solid var(--org-line);
  pointer-events: none;
}

.structure-tree li::before {
  right: 50%;
}

.structure-tree li::after {
  left: 50%;
  border-left: var(--org-line-width) solid var(--org-line);
}

.structure-tree li:first-child::before,
.structure-tree li:last-child::after {
  border: 0 none;
}

.structure-tree li:last-child::before {
  border-right: var(--org-line-width) solid var(--org-line);
}

.structure-tree li:only-child::before,
.structure-tree li:only-child::after {
  display: none;
}

.structure-tree li:only-child {
  padding-top: 0;
}

.structure-tree ul::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  width: var(--org-line-width);
  height: var(--org-gap-y);
  background: var(--org-line);
  transform: translateX(-50%);
  pointer-events: none;
}

.structure-tree > li {
  padding-top: 0;
}

.structure-tree > li::before,
.structure-tree > li::after {
  display: none;
}

html[data-theme='dark'] .structure-tree {
  --org-line: color-mix(in srgb, var(--color-on-surface) 78%, transparent);
}

/* Panel pembungkus mengikuti tema situs. */
.structure-shell {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  border: 2px solid var(--color-on-surface);
  box-shadow:
    6px 6px 0 0 var(--color-primary),
    10px 10px 0 0 color-mix(in srgb, var(--color-secondary) 65%, transparent);
  background-color: var(--color-surface-container-lowest);
  background-image:
    radial-gradient(circle at 1px 1px, color-mix(in srgb, var(--color-on-surface) 16%, transparent) 1.2px, transparent 1.6px),
    linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 10%, transparent), transparent 55%),
    linear-gradient(315deg, color-mix(in srgb, var(--color-secondary) 14%, transparent), transparent 60%);
  background-size: 20px 20px, 100% 100%, 100% 100%;
}

.structure-shell::before,
.structure-shell::after {
  content: '';
  position: absolute;
  z-index: 0;
  pointer-events: none;
}

.structure-shell::before {
  top: -72px;
  right: -48px;
  width: 180px;
  height: 180px;
  border: 2px solid color-mix(in srgb, var(--color-primary) 45%, transparent);
  border-radius: 30px;
  background: color-mix(in srgb, var(--color-primary) 16%, transparent);
  transform: rotate(16deg);
}

.structure-shell::after {
  bottom: -60px;
  left: -40px;
  width: 140px;
  height: 140px;
  border: 2px solid color-mix(in srgb, var(--color-secondary) 45%, transparent);
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-secondary) 22%, transparent);
}

.structure-toolbar {
  position: relative;
  z-index: 1;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px 16px;
  padding-bottom: 12px;
}

.structure-toolbar__hint {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-label);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.02em;
  color: var(--color-on-surface-variant);
}

.structure-toolbar__hint .material-symbols-outlined {
  font-size: 16px;
}

.structure-controls {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}

.structure-zoom {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.structure-zoom__btn,
.structure-zoom__value,
.structure-fit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  min-width: 34px;
  padding: 0 8px;
  border: 2px solid var(--color-on-surface);
  border-radius: 10px;
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
  box-shadow: 3px 3px 0 0 var(--color-primary);
  font-family: var(--font-label);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.16s var(--ease-out-soft),
    box-shadow 0.16s var(--ease-out-soft),
    background-color 0.2s var(--ease-out-soft);
}

.structure-zoom__value {
  min-width: 54px;
  background: var(--color-secondary-container);
  color: var(--color-on-secondary-container);
  font-variant-numeric: tabular-nums;
}

.structure-fit {
  gap: 5px;
  padding: 0 12px;
  background: var(--color-primary);
  color: var(--color-on-primary);
}

.structure-fit .material-symbols-outlined {
  font-size: 17px;
}

.structure-zoom__btn:hover:not(:disabled),
.structure-zoom__value:hover,
.structure-fit:hover {
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px 0 0 var(--color-primary);
}

.structure-zoom__btn:active:not(:disabled),
.structure-zoom__value:active,
.structure-fit:active {
  transform: translate(2px, 2px);
  box-shadow: 1px 1px 0 0 var(--color-primary);
}

.structure-zoom__btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.structure-zoom__btn .material-symbols-outlined {
  font-size: 18px;
}

/* Viewport kanvas: tinggi terbatas agar halaman tetap bisa di-scroll. */
.structure-viewport {
  position: relative;
  z-index: 1;
  width: 100%;
  height: clamp(380px, 74vh, 760px);
  overflow: hidden;
  touch-action: none;
  overscroll-behavior: contain;
  user-select: none;
  -webkit-user-select: none;
  cursor: grab;
  border: 2px solid var(--color-on-surface);
  border-radius: 20px;
  background-color: var(--color-surface-container-lowest);
  background-image: radial-gradient(
    circle at 1px 1px,
    color-mix(in srgb, var(--color-on-surface) 15%, transparent) 1.2px,
    transparent 1.6px
  );
  background-size: 22px 22px;
}

.structure-viewport.is-panning {
  cursor: grabbing;
}

.structure-stage {
  display: inline-block;
  width: max-content;
  padding: 32px 40px;
  transform-origin: 0 0;
  will-change: transform;
  opacity: 0;
  transition: opacity 0.18s var(--ease-out-soft);
}

.structure-stage.is-ready {
  opacity: 1;
}

@media (max-width: 640px) {
  .structure-board {
    overflow-x: clip;
    padding-left: 12px;
    padding-right: 12px;
  }

  .structure-shell {
    padding: 12px;
    border-radius: 20px;
    box-shadow:
      4px 4px 0 0 var(--color-primary),
      7px 7px 0 0 color-mix(in srgb, var(--color-secondary) 65%, transparent);
  }

  .structure-shell::before {
    top: -44px;
    right: -30px;
    width: 110px;
    height: 110px;
    border-radius: 22px;
  }

  .structure-shell::after {
    bottom: -38px;
    left: -26px;
    width: 86px;
    height: 86px;
  }

  .structure-toolbar {
    padding-bottom: 10px;
  }

  .structure-zoom__btn,
  .structure-zoom__value,
  .structure-fit {
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
  }

  .structure-zoom__value {
    min-width: 50px;
  }

  .structure-stage {
    padding: 24px 28px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .structure-stage {
    transition: none;
  }
}
</style>
