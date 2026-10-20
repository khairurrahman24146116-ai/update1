<template>
  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3">
      <h3 class="font-bold text-sm flex items-center gap-2">
        <span class="material-symbols-outlined !w-4 !h-4">auto_delete</span>
        Retensi Log Aktivitas
      </h3>
      <p class="text-xs text-on-surface-variant mt-1">Penghapusan berjalan manual. Tidak ada scheduler otomatis.</p>
    </div>
    <div class="p-4 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-3 items-end">
        <div class="space-y-1">
          <label class="block text-xs font-bold">Simpan log selama (bulan)</label>
          <input v-model.number="months" type="number" min="1" max="120" class="input w-40" />
          <p class="text-xs text-on-surface-variant">Batas 1–120 bulan. Default 12 bulan.</p>
        </div>
        <button @click="save" :disabled="saving || !valid" class="btn btn--primary btn--sm disabled:opacity-40">
          {{ saving ? 'Menyimpan…' : 'Simpan Masa Simpan' }}
        </button>
      </div>

      <div class="p-3 bg-surface-container-low border border-outline-variant rounded-lg text-xs space-y-1">
        <p><strong>Masa simpan aktif:</strong> {{ current.months ?? '—' }} bulan</p>
        <p><strong>Batas hapus:</strong> {{ preview.cutoff || '—' }}</p>
        <p><strong>Log lebih lama dari batas:</strong> {{ preview.count ?? 0 }} baris</p>
      </div>

      <div class="p-3 border border-red-300 bg-red-50 rounded-lg space-y-2">
        <p class="text-xs text-error font-bold">Hapus permanen log sebelum {{ preview.cutoff || '—' }}. Tindakan ini tidak dapat dibatalkan.</p>
        <button @click="prune" :disabled="pruning || !preview.count || !valid" class="btn btn--destructive btn--sm disabled:opacity-40">
          <span class="material-symbols-outlined !w-4 !h-4">delete_forever</span>
          {{ pruning ? 'Menghapus…' : `Hapus ${preview.count ?? 0} Log Lama` }}
        </button>
      </div>

      <p v-if="message" class="text-xs font-bold px-3 py-2 rounded-lg border" :class="messageType === 'error' ? 'bg-red-50 text-error border-red-600' : 'bg-green-50 text-green-700 border-green-600'">{{ message }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'

const emit = defineEmits(['pruned'])

const months = ref(12)
const current = ref({ months: 12, cutoff: '', count: 0 })
const preview = ref({ months: 12, cutoff: '', count: 0 })
const saving = ref(false)
const pruning = ref(false)
const message = ref('')
const messageType = ref('success')
const token = () => localStorage.getItem('madani_token')
const auth = () => ({ Authorization: `Bearer ${token()}` })
let timer = null

const valid = computed(() => Number.isInteger(months.value) && months.value >= 1 && months.value <= 120)

function notify(text, type = 'success') {
  message.value = text
  messageType.value = type
  window.clearTimeout(notify.timer)
  notify.timer = window.setTimeout(() => { message.value = '' }, 5000)
}

async function load() {
  const response = await fetch('/api/activity-logs/retention', { headers: auth() })
  if (!response.ok) return
  const data = await response.json()
  current.value = data
  preview.value = data
  months.value = data.months
}

async function loadPreview() {
  if (!valid.value) return
  const response = await fetch(`/api/activity-logs/retention/preview?months=${months.value}`, { headers: auth() })
  if (!response.ok) return
  preview.value = await response.json()
}

async function save() {
  if (!valid.value || saving.value) return
  saving.value = true
  try {
    const response = await fetch('/api/activity-logs/retention', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', ...auth() },
      body: JSON.stringify({ months: months.value }),
    })
    const data = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(data.message || 'Gagal menyimpan masa simpan.')
    current.value = data
    preview.value = data
    notify(`Masa simpan diatur ke ${data.months} bulan.`)
  } catch (error) {
    notify(error.message || 'Gagal menyimpan masa simpan.', 'error')
  } finally {
    saving.value = false
  }
}

async function prune() {
  if (!preview.value.count || pruning.value || !valid.value) return
  const confirmed = window.confirm(`Hapus permanen ${preview.value.count} log sebelum ${preview.value.cutoff}? Tindakan ini tidak dapat dibatalkan.`)
  if (!confirmed) return
  pruning.value = true
  try {
    const response = await fetch('/api/activity-logs/prune', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth() },
      body: JSON.stringify({ confirm: true, months: months.value }),
    })
    const data = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(data.message || 'Gagal menghapus log lama.')
    notify(data.message || 'Log lama dihapus.')
    emit('pruned')
    await load()
  } catch (error) {
    notify(error.message || 'Gagal menghapus log lama.', 'error')
  } finally {
    pruning.value = false
  }
}

watch(months, () => {
  window.clearTimeout(timer)
  if (!valid.value) return
  timer = window.setTimeout(loadPreview, 350)
})

onMounted(load)
</script>
