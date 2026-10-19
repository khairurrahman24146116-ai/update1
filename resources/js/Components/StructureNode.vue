<template>
  <li class="structure-node">
    <div class="structure-card">
      <div class="structure-card__photo">
        <img
          v-if="photoSrc && !failed"
          :src="photoSrc"
          :alt="`Foto ${displayName}`"
          loading="lazy"
          @error="failed = true"
        />
        <span v-else class="structure-card__initials" aria-hidden="true">{{ initials }}</span>
      </div>
      <div class="structure-card__meta">
        <p class="structure-card__name">{{ displayName }}</p>
        <p class="structure-card__position">{{ node.position }}</p>
      </div>
    </div>
    <ul v-if="children.length" class="structure-children">
      <StructureNode v-for="child in children" :key="child.id" :node="child" />
    </ul>
  </li>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

defineOptions({ name: 'StructureNode' })

const props = defineProps({
  node: { type: Object, required: true },
})

const failed = ref(false)
watch(() => props.node?.photo, () => { failed.value = false })

const children = computed(() => props.node?.children || [])

const displayName = computed(() => (props.node?.name || '').trim() || 'Belum diisi')

const initials = computed(() => {
  const name = (props.node?.name || '').trim()
  if (!name) {
    return '?'
  }
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

const photoSrc = computed(() => {
  const path = props.node?.photo
  if (!path) {
    return null
  }
  if (/^https?:\/\//.test(path) || String(path).startsWith('/')) {
    return path
  }
  return `${location.origin}/storage/${path}`
})
</script>

<style scoped>
.structure-card {
  width: 160px;
  background: var(--color-surface-container-lowest);
  border: 2px solid var(--color-primary);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-0-hover);
  display: flex;
  flex-direction: column;
  text-align: center;
  transition:
    transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.structure-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--lift-shadow);
}
.structure-card__photo {
  height: 150px;
  background: var(--color-surface-container-low);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-bottom: 2px solid var(--color-primary);
}
.structure-card__photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.structure-card__initials {
  font-family: var(--font-headline, inherit);
  font-size: 34px;
  font-weight: 700;
  color: var(--color-primary);
}
.structure-card__meta {
  background: var(--color-secondary-fixed);
  color: var(--color-on-secondary-fixed);
  padding: 8px 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-height: 74px;
}
.structure-card__name {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.25;
  overflow-wrap: anywhere;
}
.structure-card__position {
  font-size: 11px;
  line-height: 1.3;
  font-weight: 500;
  overflow-wrap: anywhere;
}
@media (max-width: 640px) {
  .structure-card {
    width: 100%;
    max-width: 240px;
    margin-inline: auto;
    border-radius: 14px;
  }
  .structure-card__photo {
    height: 100px;
  }
  .structure-card__photo img {
    object-position: top center;
  }
  .structure-card__initials {
    font-size: 26px;
  }
  .structure-card__meta {
    min-height: 0;
    padding: 10px 12px;
    gap: 3px;
  }
  .structure-card__name {
    font-size: 14px;
    line-height: 1.3;
  }
  .structure-card__position {
    font-size: 12px;
    line-height: 1.3;
  }
}
@media (prefers-reduced-motion: reduce) {
  .structure-card:hover {
    transform: none;
  }
}
</style>
