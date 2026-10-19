<template>
  <li class="structure-node">
    <article class="structure-card">
      <div class="structure-card__photo">
        <img
          v-if="photoSrc && !failed"
          :src="photoSrc"
          :alt="`Foto ${displayName}`"
          loading="lazy"
          decoding="async"
          draggable="false"
          @error="failed = true"
        />
        <span v-else class="structure-card__initials" aria-hidden="true">{{ initials }}</span>
      </div>
      <div class="structure-card__meta">
        <p class="structure-card__name" :title="displayName">{{ displayName }}</p>
        <p class="structure-card__position" :title="positionText">{{ positionText }}</p>
      </div>
    </article>
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

const positionText = computed(() => (props.node?.position || '').trim() || '—')

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
  display: flex;
  flex-direction: column;
  width: 140px;
  overflow: hidden;
  text-align: center;
  background: #ffffff;
  color: #111111;
  border: 3px solid #111111;
  border-radius: 12px;
  box-shadow: 0 4px 0 rgba(17, 17, 17, 0.16);
  transition:
    box-shadow 0.25s var(--ease-out-soft),
    transform 0.25s var(--ease-out-soft);
}

.structure-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 7px 0 rgba(17, 17, 17, 0.22);
}

.structure-card__photo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 150px;
  overflow: hidden;
  background: #ececec;
  border-bottom: 2px solid #111111;
}

.structure-card__photo img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
}

.structure-card__initials {
  font-family: var(--font-headline, inherit);
  font-size: 30px;
  font-weight: 800;
  letter-spacing: 0.02em;
  color: #111111;
  background: #ececec;
}

.structure-card__meta {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  min-height: 66px;
  padding: 8px 7px 10px;
  background: #fff200;
  color: #111111;
}

.structure-card__name {
  margin: 0;
  font-family: var(--font-label, inherit);
  font-size: 12.5px;
  font-weight: 800;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.structure-card__position {
  margin: 0;
  font-family: var(--font-label, inherit);
  font-size: 10px;
  font-weight: 700;
  line-height: 1.25;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  overflow-wrap: anywhere;
}

@media (prefers-reduced-motion: reduce) {
  .structure-card {
    transition: none;
  }

  .structure-card:hover {
    transform: none;
  }
}
</style>
