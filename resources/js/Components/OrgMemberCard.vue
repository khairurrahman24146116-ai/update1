<template>
  <div class="org-card">
    <div class="org-card__photo">
      <img
        v-if="photoSrc && !failed"
        :src="photoSrc"
        :alt="`Foto ${displayName}`"
        loading="lazy"
        @error="failed = true"
      />
      <span v-else class="material-symbols-outlined org-card__ph" aria-hidden="true">person</span>
    </div>
    <div class="org-card__meta">
      <p class="org-card__name">{{ displayName }}</p>
      <p class="org-card__position">{{ member.position }}</p>
      <span v-if="pending" class="org-card__badge">
        <span class="material-symbols-outlined" aria-hidden="true">error</span>
        Perlu konfirmasi
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  member: { type: Object, required: true },
})

const failed = ref(false)

watch(() => props.member?.photo, () => { failed.value = false })

const displayName = computed(() => (props.member?.name || '').trim() || 'Belum diisi')

const pending = computed(() => !props.member?.is_confirmed || !(props.member?.name || '').trim())

const photoSrc = computed(() => {
  const path = props.member?.photo
  if (!path) return null
  if (/^https?:\/\//.test(path) || String(path).startsWith('/')) return path
  return `${location.origin}/storage/${path}`
})
</script>

<style scoped>
.org-card {
  width: 158px;
  background: var(--color-surface-container-lowest);
  border: 2px solid var(--color-primary);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: var(--shadow-0-hover);
  display: flex;
  flex-direction: column;
  text-align: center;
  transition:
    transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1),
    border-color 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.org-card:hover {
  transform: translateY(-3px);
  border-color: var(--color-primary);
  box-shadow: var(--lift-shadow);
}
.org-card__photo {
  height: 150px;
  background: var(--color-surface-container-low);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-bottom: 2px solid var(--color-primary);
}
.org-card__photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.org-card__ph {
  font-size: 46px;
  color: var(--color-outline);
}
.org-card__meta {
  background: var(--color-secondary-fixed);
  color: var(--color-on-secondary-fixed);
  padding: 8px 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-height: 74px;
}
.org-card__name {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.25;
  overflow-wrap: anywhere;
}
.org-card__position {
  font-size: 11px;
  line-height: 1.3;
  font-weight: 500;
  overflow-wrap: anywhere;
}
.org-card__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  margin-top: 2px;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  background: var(--color-tertiary-fixed);
  color: var(--color-on-tertiary-fixed);
  border-radius: 999px;
  padding: 2px 8px;
  align-self: center;
}
.org-card__badge .material-symbols-outlined {
  font-size: 11px;
  width: 11px;
  height: 11px;
}
@media (prefers-reduced-motion: reduce) {
  .org-card:hover { transform: none; }
}
</style>
