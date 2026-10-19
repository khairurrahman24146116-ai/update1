<template>
  <section class="w-full max-w-7xl mx-auto px-gutter pb-space-lg">
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

    <div v-else class="mt-space-md rounded-[28px] border border-outline-variant bg-surface-container-lowest shadow-sm p-space-md md:p-space-lg">
      <ul class="structure-tree">
        <StructureNode v-for="root in tree" :key="root.id" :node="root" />
      </ul>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
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

@media (max-width: 639px) {
  .structure-tree,
  .structure-tree ul {
    display: block;
    padding-top: 0;
  }
  .structure-tree li,
  .structure-tree > li {
    display: block;
    padding: 0;
  }
  .structure-tree li::before,
  .structure-tree li::after,
  .structure-tree ul::before {
    display: none;
  }
  .structure-tree .structure-children {
    display: block;
    border-left: 2px solid var(--color-outline-variant);
    margin: 8px 0 0 16px;
    padding: 0 0 0 12px;
  }
  .structure-tree .structure-node > .structure-card {
    width: 100%;
    max-width: 260px;
    margin: 0 auto;
  }
  .structure-tree li + li {
    margin-top: 12px;
  }
}
</style>
