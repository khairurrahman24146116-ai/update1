<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <label v-if="isAdmin" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-surface-container-low border border-outline-variant font-label-sm text-label-sm text-on-surface-variant">
          <span class="material-symbols-outlined !w-4 !h-4">swap_horiz</span>
          <select v-model="selectedUnit" class="bg-transparent border-0 text-xs font-bold focus:outline-none" aria-label="Pilih unit" @change="changeUnit">
            <option value="sma">Unit SMA</option>
            <option value="lpi">Unit LPI</option>
          </select>
        </label>
        <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed border border-outline-variant font-label-sm text-label-sm font-bold text-primary uppercase tracking-wider">
          <span class="material-symbols-outlined !w-4 !h-4">account_balance</span>
          Unit {{ unitLabel }}
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container-low border border-outline-variant font-label-sm text-label-sm text-on-surface-variant">
          <span class="material-symbols-outlined !w-4 !h-4">shield_person</span>
          {{ roleLabel }}
        </span>
      </div>
      <div v-if="showPeriod" class="flex flex-wrap items-center gap-2">
        <label class="font-label-sm text-label-sm text-on-surface-variant" for="finance-from">Periode</label>
        <input id="finance-from" v-model="from" type="date" class="input w-40" @change="emitRange" />
        <span class="text-on-surface-variant">s/d</span>
        <input v-model="to" type="date" class="input w-40" @change="emitRange" />
        <button type="button" class="btn btn--ghost btn--sm" @click="resetRange">Reset</button>
      </div>
    </div>

    <nav class="flex gap-2 overflow-x-auto pb-1 p-1 bg-surface-container-low border border-outline-variant rounded-xl shadow-sm" aria-label="Navigasi keuangan">
      <router-link
        v-for="item in visibleItems"
        :key="item.to"
        :to="item.to"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap border transition-colors"
        :class="isActive(item.to) ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-white hover:bg-surface-container-high border-transparent text-on-surface'"
      >
        <span class="material-symbols-outlined !w-4 !h-4">{{ item.icon }}</span>
        {{ item.label }}
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { financeUnit, setFinanceUnit } from '../composables/useFinance.js'
import { can } from '../permissions.js'

const props = defineProps({
  showPeriod: { type: Boolean, default: false },
  from: { type: String, default: '' },
  to: { type: String, default: '' },
})
const emit = defineEmits(['update:from', 'update:to', 'range'])

const route = useRoute()
const { user } = useAuth()
const from = ref(props.from)
const to = ref(props.to)
const isAdmin = computed(() => ['admin_utama', 'admin'].includes(user.value?.role))
const selectedUnit = ref(financeUnit())

watch(() => props.from, (v) => { from.value = v })
watch(() => props.to, (v) => { to.value = v })

const unit = computed(() => {
  if (isAdmin.value) return selectedUnit.value
  const codes = Array.isArray(user.value?.finance_unit_codes) ? user.value.finance_unit_codes : []
  if (codes.length) return codes[0]
  if (user.value?.role === 'bendahara_lpi') return 'lpi'
  if (user.value?.role === 'bendahara_sma') return 'sma'
  return user.value?.unit || 'sma'
})
const unitLabel = computed(() => unit.value.toUpperCase())
const roleLabel = computed(() => {
  const map = { admin: 'Admin', bendahara_lpi: 'Bendahara LPI', bendahara_sma: 'Bendahara SMA' }
  return map[user.value?.role] || 'Bendahara'
})

function changeUnit() {
  setFinanceUnit(selectedUnit.value)
  window.location.reload()
}

const base = '/app/bendahara'
const items = [
  { to: base, label: 'Dashboard', icon: 'dashboard', permission: 'finance.view' },
  { to: `${base}/pemasukan`, label: 'Pemasukan', icon: 'trending_up', permission: 'finance.transactions.view' },
  { to: `${base}/pengeluaran`, label: 'Pengeluaran', icon: 'trending_down', permission: 'finance.transactions.view' },
  { to: `${base}/spp`, label: 'SPP', icon: 'receipt_long', permission: 'spp.view' },
  { to: `${base}/pembayaran`, label: 'Riwayat Pembayaran', icon: 'history', permission: 'spp.view' },
  { to: `${base}/laporan`, label: 'Laporan', icon: 'assessment', permission: 'reports.finance.view' },
]
const visibleItems = computed(() => items.filter((item) => can(user.value, item.permission)))

function isActive(path) {
  return route.path === path
}

function emitRange() {
  emit('update:from', from.value)
  emit('update:to', to.value)
  emit('range', { from: from.value, to: to.value })
}

function resetRange() {
  from.value = ''
  to.value = ''
  emitRange()
}
</script>

