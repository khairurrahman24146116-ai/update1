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
        <p class="structure-toolbar__hint">Perbesar atau perkecil bagan</p>
        <div class="structure-zoom" role="group" aria-label="Kontrol zoom bagan">
          <button type="button" class="structure-zoom__btn" :disabled="zoom <= minZoom" aria-label="Perkecil bagan" @click="step(-0.1)">
            <span class="material-symbols-outlined" aria-hidden="true">remove</span>
          </button>
          <button type="button" class="structure-zoom__value" aria-label="Reset zoom bagan" @click="reset">
            {{ Math.round(zoom * 100) }}%
          </button>
          <button type="button" class="structure-zoom__btn" :disabled="zoom >= maxZoom" aria-label="Perbesar bagan" @click="step(0.1)">
            <span class="material-symbols-outlined" aria-hidden="true">add</span>
          </button>
        </div>
      </div>
      <div class="structure-viewport" :style="viewportStyle">
        <div ref="stageEl" class="structure-stage" :style="stageStyle">
          <ul class="structure-tree">
            <StructureNode v-for="root in tree" :key="root.id" :node="root" />
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch(`/api/structure/${props.unit}`, { headers: { Accept: 'application/json' } })
    if (!res.ok) {
      throw new Error('Gagal memuat struktur organisasi.')
    }
    const json = await res.json()
    tree.value = Array.isArray(json.tree) ? json.tree : []
  } catch (e) {
    error.value = e.message || 'Gagal memuat struktur organisasi.'
  } finally {
    loading.value = false
  }
}

const minZoom = 0.7
const maxZoom = 1.4
const zoom = ref(1)
const stageEl = ref(null)
const stageHeight = ref(0)
let resizeObserver = null

function step(delta) {
  const next = Math.round((zoom.value + delta) * 10) / 10
  zoom.value = Math.min(maxZoom, Math.max(minZoom, next))
}

function reset() {
  zoom.value = 1
}

const viewportStyle = computed(() => (
  stageHeight.value
    ? { height: `${Math.ceil(stageHeight.value * zoom.value)}px` }
    : {}
))

const stageStyle = computed(() => ({
  transform: `scale(${zoom.value})`,
  transformOrigin: zoom.value <= 1 ? 'top center' : 'top left',
}))

watch(stageEl, (el) => {
  resizeObserver?.disconnect()
  if (!el) {
    stageHeight.value = 0
    return
  }
  resizeObserver = new ResizeObserver(() => {
    stageHeight.value = el.offsetHeight
  })
  resizeObserver.observe(el)
  stageHeight.value = el.offsetHeight
})

onBeforeUnmount(() => resizeObserver?.disconnect())

onMounted(load)
</script>

<style>
.structure-tree,
.structure-tree ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.structure-tree ul {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: flex-start;
  padding-top: 30px;
  position: relative;
}
.structure-tree li {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 12px 0;
}
.structure-tree li::before,
.structure-tree li::after {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  width: 50%;
  height: 30px;
  border-top: 2px solid var(--color-outline);
}
.structure-tree li::after {
  right: auto;
  left: 50%;
  border-left: 2px solid var(--color-outline);
}
.structure-tree li:only-child::before,
.structure-tree li:only-child::after {
  display: none;
}
.structure-tree li:only-child {
  padding-top: 0;
}
.structure-tree li:first-child::before,
.structure-tree li:last-child::after {
  border: 0 none;
}
.structure-tree li:last-child::before {
  border-right: 2px solid var(--color-outline);
  border-radius: 0 8px 0 0;
}
.structure-tree li:first-child::after {
  border-radius: 8px 0 0 0;
}
.structure-tree ul ul::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  width: 0;
  height: 30px;
  border-left: 2px solid var(--color-outline);
}
.structure-tree > li {
  padding-top: 0;
}
.structure-tree > li::before,
.structure-tree > li::after {
  display: none;
}

/* Panel bergaya neobrutalism, warna tetap mengikuti token tema. */
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
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
}
.structure-toolbar__hint {
  font-family: var(--font-label);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.02em;
  color: var(--color-on-surface-variant);
}
.structure-zoom {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.structure-zoom__btn,
.structure-zoom__value {
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
.structure-zoom__btn:hover:not(:disabled),
.structure-zoom__value:hover {
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px 0 0 var(--color-primary);
}
.structure-zoom__btn:active:not(:disabled),
.structure-zoom__value:active {
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

.structure-viewport {
  position: relative;
  z-index: 1;
  width: 100%;
  overflow: auto;
}
.structure-stage {
  width: 100%;
  transform-origin: top center;
  transition: transform 0.28s var(--ease-out-soft);
}

@media (max-width: 640px) {
  .structure-board {
    padding-left: 12px;
    padding-right: 12px;
    overflow-x: hidden;
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
  .structure-toolbar__hint {
    display: none;
  }
  .structure-zoom__btn,
  .structure-zoom__value {
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
  }
  .structure-zoom__value {
    min-width: 50px;
  }
  .structure-tree,
  .structure-tree ul {
    display: block;
    width: 100%;
    padding-top: 0;
  }
  .structure-tree li,
  .structure-tree > li {
    display: block;
    width: 100%;
    min-width: 0;
    padding: 0;
  }
  .structure-tree li::before,
  .structure-tree li::after,
  .structure-tree ul::before {
    display: none;
  }
  .structure-tree .structure-children {
    position: relative;
    display: block;
    width: auto;
    margin: 0;
    padding: 18px 0 0;
    border-left: 0;
  }
  .structure-tree .structure-children::before {
    content: '';
    display: block;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    width: 2px;
    transform: translateX(-1px);
    border: 0;
    border-radius: 0;
    background: var(--color-outline);
  }
  .structure-tree .structure-children::after {
    content: '';
    display: block;
    position: absolute;
    top: -6px;
    left: 50%;
    width: 12px;
    height: 12px;
    transform: translateX(-50%);
    border: 2px solid var(--color-on-surface);
    border-radius: 3px;
    background: var(--color-primary);
  }
  .structure-tree .structure-children > .structure-node {
    position: relative;
  }
  .structure-tree .structure-children > .structure-node::before {
    content: '';
    display: block;
    position: absolute;
    top: -18px;
    left: 50%;
    width: 2px;
    height: 18px;
    transform: translateX(-1px);
    border: 0;
    background: var(--color-outline);
  }
  .structure-tree .structure-node > .structure-card {
    width: 100%;
    max-width: 260px;
    margin: 0 auto;
  }
  .structure-tree li + li {
    margin-top: 18px;
  }
}
</style>
