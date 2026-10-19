<template>
  <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 p-0 sm:p-4" role="dialog" aria-modal="true" @click.self="$emit('close')">
    <div class="bg-white border border-outline-variant rounded-t-2xl sm:rounded-xl shadow-xl w-full sm:max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
      <header class="px-4 py-3 border-b border-outline-variant flex items-start justify-between gap-3 bg-surface-container-low">
        <div class="min-w-0">
          <h2 class="font-headline-sm text-headline-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">key</span> Hak Akses
          </h2>
          <p class="text-xs text-on-surface-variant truncate">
            {{ acc.name }} <span class="opacity-70">({{ acc.email }})</span> · {{ labelRole(acc.role) }}
            <span v-if="acc.unit" class="ml-1 uppercase">· Unit {{ acc.unit }}</span>
          </p>
        </div>
        <button type="button" class="btn btn--ghost btn--sm shrink-0" @click="$emit('close')" aria-label="Tutup">
          <span class="material-symbols-outlined !w-4 !h-4">close</span>
        </button>
      </header>

      <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat hak akses...</div>
      <div v-else-if="error" class="p-6 space-y-3">
        <p class="text-sm text-error bg-red-50 border border-red-600 rounded-xl px-3 py-2">{{ error }}</p>
        <button class="btn btn--ghost btn--sm" @click="load">Coba lagi</button>
      </div>

      <template v-else>
        <div v-if="!data.can_manage" class="mx-4 mt-3 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-400 rounded-xl px-3 py-2 flex items-start gap-2">
          <span class="material-symbols-outlined !w-4 !h-4">lock</span>
          <span>{{ data.cannot_manage_reason || 'Hak akses hanya dapat dilihat.' }}</span>
        </div>

        <div class="px-4 py-3 flex flex-wrap items-center gap-2 border-b border-outline-variant">
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-surface-container-low border border-outline-variant text-[11px] font-bold">
            <span class="material-symbols-outlined !w-3.5 !h-3.5">badge</span> Bawaan role: {{ data.role_permissions.length }}
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-50 border border-green-600 text-green-700 text-[11px] font-bold">
            + Tambahan: {{ allow.size }}
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 border border-red-600 text-red-700 text-[11px] font-bold">
            − Dicabut: {{ deny.size }}
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary-fixed border border-outline-variant text-primary text-[11px] font-bold">
            Efektif: {{ effectiveCount }}
          </span>
          <span v-if="dirty" class="ml-auto inline-flex items-center gap-1 text-[11px] font-bold text-amber-700">
            <span class="material-symbols-outlined !w-3.5 !h-3.5">edit</span> Belum disimpan
          </span>
        </div>

        <div class="px-4 py-2 border-b border-outline-variant">
          <input v-model="search" placeholder="Cari izin..." class="input w-full" />
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
          <details v-for="m in filteredModules" :key="m.key" class="border border-outline-variant rounded-xl overflow-hidden" :open="!!search || openModules.has(m.key)">
            <summary class="px-3 py-2 bg-surface-container-low cursor-pointer flex items-center gap-2 select-none">
              <span class="material-symbols-outlined !w-4 !h-4 text-primary">folder</span>
              <span class="font-bold text-sm">{{ m.label }}</span>
              <span class="text-[11px] text-on-surface-variant ml-auto">{{ moduleActiveCount(m) }}/{{ m.permissions.length }}</span>
            </summary>
            <div class="p-3 space-y-2">
              <p class="text-xs text-on-surface-variant">{{ m.description }}</p>
              <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn--ghost btn--sm" :disabled="!data.can_manage" @click="enableModule(m)">Aktifkan semua</button>
                <button type="button" class="btn btn--ghost btn--sm" :disabled="!data.can_manage" @click="resetModule(m)">Kembalikan bawaan</button>
              </div>
              <label
                v-for="p in m.permissions"
                :key="p.key"
                class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-surface-container-low"
                :class="!canToggle(p) ? 'opacity-60' : 'cursor-pointer'"
              >
                <input
                  type="checkbox"
                  class="accent-primary"
                  :checked="isEffective(p.key)"
                  :disabled="!canToggle(p)"
                  @change="toggle(p)"
                />
                <span class="text-xs font-bold">{{ p.label }}</span>
                <code class="text-[10px] text-on-surface-variant">{{ p.key }}</code>
                <span v-if="p.privileged" class="px-1.5 py-0.5 rounded-full bg-amber-50 border border-amber-400 text-amber-800 text-[10px] font-bold">Sensitif</span>
                <span v-if="sourceOf(p.key) === 'allow'" class="ml-auto px-1.5 py-0.5 rounded-full bg-green-50 border border-green-600 text-green-700 text-[10px] font-bold">Ditambahkan</span>
                <span v-else-if="sourceOf(p.key) === 'deny'" class="ml-auto px-1.5 py-0.5 rounded-full bg-red-50 border border-red-600 text-red-700 text-[10px] font-bold">Dicabut</span>
                <span v-else-if="sourceOf(p.key) === 'role'" class="ml-auto text-[10px] text-on-surface-variant">Bawaan role</span>
              </label>
            </div>
          </details>
          <p v-if="!filteredModules.length" class="text-center text-sm text-on-surface-variant py-6">Tidak ada izin yang cocok.</p>
        </div>

        <footer class="px-4 py-3 border-t border-outline-variant flex flex-wrap items-center gap-2 bg-surface-container-low">
          <p v-if="msg" class="text-xs font-bold mr-auto" :class="msgType === 'error' ? 'text-error' : 'text-green-700'">{{ msg }}</p>
          <span v-else class="mr-auto"></span>
          <button type="button" class="btn btn--ghost btn--sm" @click="$emit('close')">Tutup</button>
          <button type="button" class="btn btn--primary btn--sm" :disabled="!data.can_manage || saving || !dirty" @click="save">
            <span class="material-symbols-outlined !w-4 !h-4">save</span>
            {{ saving ? 'Menyimpan...' : 'Simpan Hak Akses' }}
          </button>
        </footer>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
const props = defineProps({ acc: { type: Object, required: true } })
const emit = defineEmits(['close', 'saved'])

const data = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const msg = ref('')
const msgType = ref('success')
const search = ref('')
const allow = ref(new Set())
const deny = ref(new Set())
const initial = ref({ allow: [], deny: [] })
const openModules = ref(new Set(['students']))

const roleSet = computed(() => new Set(data.value?.role_permissions || []))
const privileged = computed(() => new Set(data.value?.privileged || []))
const effectiveCount = computed(() => {
  const all = data.value?.modules.flatMap((m) => m.permissions.map((p) => p.key)) || []
  return all.filter((p) => isEffective(p)).length
})
const dirty = computed(() => {
  const a = [...allow.value].sort().join(',')
  const d = [...deny.value].sort().join(',')
  return a !== [...initial.value.allow].sort().join(',') || d !== [...initial.value.deny].sort().join(',')
})
const filteredModules = computed(() => {
  const q = search.value.trim().toLowerCase()
  const modules = data.value?.modules || []
  if (!q) return modules
  return modules
    .map((m) => ({ ...m, permissions: m.permissions.filter((p) => p.label.toLowerCase().includes(q) || p.key.toLowerCase().includes(q)) }))
    .filter((m) => m.permissions.length)
})

function labelRole(r) { return ({ admin: 'Admin', guru: 'Guru', bendahara_lpi: 'Bendahara LPI', bendahara_sma: 'Bendahara SMA', wali_murid: 'Wali Murid' })[r] || r }
function isEffective(key) {
  if (deny.value.has(key)) return false
  if (allow.value.has(key)) return true
  return roleSet.value.has(key)
}
function sourceOf(key) {
  if (allow.value.has(key)) return 'allow'
  if (deny.value.has(key)) return 'deny'
  return roleSet.value.has(key) ? 'role' : 'none'
}
function canToggle(p) {
  if (!data.value?.can_manage) return false
  if (privileged.value.has(p.key) && data.value.user?.role !== 'admin') return false
  return true
}
function toggle(p) {
  if (!canToggle(p)) return
  const key = p.key
  if (isEffective(key)) {
    if (roleSet.value.has(key)) { deny.value.add(key); allow.value.delete(key) }
    else { allow.value.delete(key); deny.value.delete(key) }
  } else {
    if (roleSet.value.has(key)) { deny.value.delete(key) }
    else { allow.value.add(key); deny.value.delete(key) }
  }
}
function moduleActiveCount(m) { return m.permissions.filter((p) => isEffective(p.key)).length }
function enableModule(m) {
  m.permissions.forEach((p) => { if (canToggle(p) && !isEffective(p.key)) toggle(p) })
}
function resetModule(m) {
  m.permissions.forEach((p) => { allow.value.delete(p.key); deny.value.delete(p.key) })
}
async function load() {
  loading.value = true; error.value = ''
  try {
    const r = await fetch(`/api/users/${props.acc.id}/permissions`, { headers: { Authorization: `Bearer ${localStorage.getItem('madani_token')}` } })
    if (!r.ok) throw new Error('Gagal memuat hak akses')
    data.value = await r.json()
    allow.value = new Set(data.value.allow || [])
    deny.value = new Set(data.value.deny || [])
    initial.value = { allow: data.value.allow || [], deny: data.value.deny || [] }
  } catch (e) { error.value = e.message } finally { loading.value = false }
}
async function save() {
  if (dirty.value && !confirm('Simpan perubahan hak akses untuk akun ini?')) return
  saving.value = true; msg.value = ''
  try {
    const r = await fetch(`/api/users/${props.acc.id}/permissions`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('madani_token')}` },
      body: JSON.stringify({ allow: [...allow.value], deny: [...deny.value] }),
    })
    const body = await r.json().catch(() => ({}))
    if (!r.ok) { msgType.value = 'error'; msg.value = body.message || Object.values(body.errors || {}).flat().join(', ') || 'Gagal menyimpan'; return }
    data.value = body
    allow.value = new Set(body.allow || [])
    deny.value = new Set(body.deny || [])
    initial.value = { allow: body.allow || [], deny: body.deny || [] }
    msgType.value = 'success'; msg.value = 'Hak akses tersimpan'
    emit('saved', body)
  } finally { saving.value = false }
}
onMounted(load)
</script>
