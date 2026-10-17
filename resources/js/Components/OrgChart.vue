<template>
  <div class="org-chart">
    <div class="flex items-center justify-between gap-space-sm pb-space-sm">
      <p class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
        <span class="material-symbols-outlined text-[16px]">pan_tool</span>
        Geser bagan untuk melihat seluruh bagian.
      </p>
      <div class="flex items-center gap-1" role="group" aria-label="Kontrol zoom bagan">
        <button type="button" class="org-zoom-btn control-glass" :disabled="zoom <= minZoom" aria-label="Perkecil bagan" @click="step(-0.1)">
          <span class="material-symbols-outlined">remove</span>
        </button>
        <button type="button" class="org-zoom-btn control-glass w-auto px-3 font-label-sm text-label-sm" aria-label="Reset tampilan bagan" @click="reset">
          {{ Math.round(zoom * 100) }}%
        </button>
        <button type="button" class="org-zoom-btn control-glass" :disabled="zoom >= maxZoom" aria-label="Perbesar bagan" @click="step(0.1)">
          <span class="material-symbols-outlined">add</span>
        </button>
      </div>
    </div>

    <div class="org-viewport">
      <div class="org-stage" :style="{ transform: `scale(${zoom})` }">
        <ul class="org-tree">
          <OrgNode v-for="root in roots" :key="root.id" :node="root" :by-parent="byParent" />
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import OrgNode from './OrgNode.vue'

const props = defineProps({
  members: { type: Array, default: () => [] },
})

const minZoom = 0.6
const maxZoom = 1.4
const zoom = ref(1)

function step(delta) {
  const next = Math.round((zoom.value + delta) * 100) / 100
  zoom.value = Math.min(maxZoom, Math.max(minZoom, next))
}
function reset() {
  zoom.value = 1
}

const byParent = computed(() => {
  const ids = new Set(props.members.map((m) => m.id))
  const map = {}
  props.members.forEach((m) => {
    const key = m.parent_id && ids.has(m.parent_id) ? m.parent_id : 'root'
    ;(map[key] = map[key] || []).push(m)
  })
  return map
})

const roots = computed(() => byParent.value.root || [])
</script>

<style scoped>
.org-zoom-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 9999px;
  color: var(--color-on-surface);
  cursor: pointer;
}
.org-zoom-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.org-zoom-btn .material-symbols-outlined {
  font-size: 18px;
}
.org-viewport {
  width: 100%;
  overflow: auto;
  max-height: 75vh;
  border: 1px solid var(--color-outline-variant);
  border-radius: 20px;
  background:
    radial-gradient(circle at 1px 1px, var(--color-outline-variant) 1px, transparent 0) 0 0 / 22px 22px,
    var(--color-surface-container-lowest);
}
.org-stage {
  transform-origin: top left;
  display: inline-block;
  min-width: min-content;
  padding: 24px;
}
</style>

<style>
.org-tree,
.org-tree ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.org-tree ul {
  display: flex;
  justify-content: center;
  padding-top: 30px;
  position: relative;
}
.org-tree li {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 12px 0;
}
.org-tree li::before,
.org-tree li::after {
  content: '';
  position: absolute;
  top: 0;
  right: 50%;
  width: 50%;
  height: 30px;
  border-top: 2px solid var(--color-outline);
}
.org-tree li::after {
  right: auto;
  left: 50%;
  border-left: 2px solid var(--color-outline);
}
.org-tree li:only-child::before,
.org-tree li:only-child::after {
  display: none;
}
.org-tree li:only-child {
  padding-top: 0;
}
.org-tree li:first-child::before,
.org-tree li:last-child::after {
  border: 0 none;
}
.org-tree li:last-child::before {
  border-right: 2px solid var(--color-outline);
  border-radius: 0 8px 0 0;
}
.org-tree li:first-child::after {
  border-radius: 8px 0 0 0;
}
.org-tree ul ul::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  width: 0;
  height: 30px;
  border-left: 2px solid var(--color-outline);
}
.org-tree > li {
  padding-top: 0;
}
.org-tree > li::before,
.org-tree > li::after {
  display: none;
}
</style>
