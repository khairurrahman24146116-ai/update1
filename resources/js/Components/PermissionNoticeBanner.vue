<template>
  <div v-if="denied || notices.length" class="space-y-2">
    <div
      v-if="denied"
      class="flex items-start gap-3 rounded-xl border border-error bg-error-bg px-4 py-3 text-error"
      role="alert"
    >
      <span class="material-symbols-outlined !h-5 !w-5 shrink-0">block</span>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-bold">Akses ditolak</p>
        <p class="text-xs leading-relaxed">Kamu tidak bisa akses fitur ini, hubungi admin.</p>
      </div>
      <button type="button" class="btn btn--ghost btn--sm shrink-0" @click="$emit('dismiss-denied')">
        Tutup
      </button>
    </div>

    <TransitionGroup name="fade" tag="div" class="space-y-2">
      <div
        v-for="notice in notices"
        :key="notice.id"
        class="flex items-start gap-3 rounded-xl border px-4 py-3"
        :class="notice.type === 'grant'
          ? 'border-success bg-success-bg text-success'
          : 'border-error bg-error-bg text-error'"
        role="status"
      >
        <span class="material-symbols-outlined !h-5 !w-5 shrink-0">
          {{ notice.type === 'grant' ? 'verified_user' : 'lock' }}
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-bold">
            {{ notice.type === 'grant' ? 'Akses diberikan' : 'Akses dicabut' }}
          </p>
          <p class="text-xs leading-relaxed">
            <template v-if="notice.type === 'grant'">
              Kamu bisa mengakses {{ notice.label }}. Akses yang sudah diberi admin, mohon digunakan secara baik dan hati-hati.
            </template>
            <template v-else>
              Kamu tidak bisa akses {{ notice.label }}. Hubungi admin.
            </template>
          </p>
        </div>
        <button type="button" class="btn btn--ghost btn--sm shrink-0" @click="$emit('dismiss', notice)">
          Tutup
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
defineProps({
  notices: { type: Array, default: () => [] },
  denied: { type: Boolean, default: false },
})

defineEmits(['dismiss', 'dismiss-denied'])
</script>
