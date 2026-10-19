<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="state.open"
        class="fixed inset-0 z-[80] bg-inverse-surface/60 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="cancel"
        @keydown.esc="cancel"
      >
        <div
          class="bg-white max-w-md w-full rounded-[28px] border border-outline-variant shadow-card p-6 space-y-4"
          role="dialog"
          aria-modal="true"
          tabindex="-1"
        >
          <div class="flex items-start gap-3">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" :class="meta.wrap">
              <span class="material-symbols-outlined text-[22px]" :class="meta.iconColor">{{ meta.icon }}</span>
            </span>
            <div class="flex-1 min-w-0">
              <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ state.title || meta.defaultTitle }}</h3>
              <p class="text-sm text-on-surface-variant mt-1 whitespace-pre-line">{{ state.message }}</p>
            </div>
          </div>

          <input
            v-if="state.type === 'prompt'"
            ref="inputEl"
            v-model="state.inputValue"
            class="w-full bg-surface-container-low px-4 py-3 rounded-md text-sm border border-outline-variant focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10"
            :placeholder="state.inputPlaceholder"
            @keydown.enter="accept"
          />

          <div class="flex gap-2 justify-end pt-1">
            <button v-if="state.type !== 'alert'" type="button" class="btn btn--ghost btn--sm" @click="cancel">
              {{ state.cancelText }}
            </button>
            <button
              type="button"
              class="btn btn--sm"
              :class="state.variant === 'danger' ? 'btn--destructive' : 'btn--primary'"
              @click="accept"
            >
              {{ state.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useDialog } from '../composables/useDialog.js'

const { state, accept, cancel } = useDialog()
const inputEl = ref(null)

function onKeydown(event) {
  if (event.key === 'Escape' && state.open) cancel()
}

watch(
  () => state.open,
  async (open) => {
    if (open) {
      window.addEventListener('keydown', onKeydown)
      if (state.type === 'prompt') {
        await nextTick()
        inputEl.value?.focus()
        inputEl.value?.select()
      }
    } else {
      window.removeEventListener('keydown', onKeydown)
    }
  },
)

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

const variants = {
  info: { icon: 'info', wrap: 'bg-primary-fixed', iconColor: 'text-primary', defaultTitle: 'Informasi' },
  success: { icon: 'check_circle', wrap: 'bg-success-bg', iconColor: 'text-success', defaultTitle: 'Berhasil' },
  warning: { icon: 'warning', wrap: 'bg-warning-bg', iconColor: 'text-warning', defaultTitle: 'Konfirmasi' },
  danger: { icon: 'delete', wrap: 'bg-error-bg', iconColor: 'text-error', defaultTitle: 'Konfirmasi' },
  error: { icon: 'error', wrap: 'bg-error-bg', iconColor: 'text-error', defaultTitle: 'Terjadi Kesalahan' },
}

const meta = computed(() => variants[state.variant] || variants.info)
</script>
