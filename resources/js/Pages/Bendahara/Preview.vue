<template>
  <FinanceShell :title="title" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div class="no-print flex flex-wrap items-center justify-between gap-2">
      <button type="button" class="btn btn--ghost btn--sm" @click="goBack">
        <span class="material-symbols-outlined !w-4 !h-4">arrow_back</span>
        Kembali
      </button>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn btn--ghost btn--sm" :disabled="loading || !rows.length" @click="downloadCsv">
          <span class="material-symbols-outlined !w-4 !h-4">download</span>
          Unduh CSV
        </button>
        <button type="button" class="btn btn--primary btn--sm" :disabled="loading" @click="print">
          <span class="material-symbols-outlined !w-4 !h-4">print</span>
          Cetak / PDF
        </button>
      </div>
    </div>

    <div v-if="error" class="no-print p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>

    <div id="preview-print" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-4">
      <header class="text-center space-y-1 border-b border-outline-variant pb-3">
        <h1 class="font-headline-sm text-headline-sm font-bold uppercase tracking-wide">{{ unitName }}</h1>
        <h2 class="font-title-md text-title-md font-semibold">{{ title }}</h2>
        <p class="text-sm text-on-surface-variant">{{ periodLabel }}</p>
      </header>

      <div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat…</div>
      <p v-else-if="!rows.length" class="p-6 text-center text-sm text-on-surface-variant">Tidak ada data pada periode ini.</p>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="bg-surface-container-low text-on-surface-variant">
                <th class="border border-outline-variant p-2 text-left w-10">No</th>
                <th class="border border-outline-variant p-2 text-left whitespace-nowrap">Tanggal</th>
                <th class="border border-outline-variant p-2 text-left">Keterangan</th>
                <th class="border border-outline-variant p-2 text-left whitespace-nowrap">Kategori</th>
                <th v-if="isExpense" class="border border-outline-variant p-2 text-left">Tujuan</th>
                <th class="border border-outline-variant p-2 text-right whitespace-nowrap">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, index) in rows" :key="t.id">
                <td class="border border-outline-variant p-2">{{ index + 1 }}</td>
                <td class="border border-outline-variant p-2 whitespace-nowrap">{{ formatDate(t.transaction_date) }}</td>
                <td class="border border-outline-variant p-2">
                  {{ t.description }}
                  <span v-if="t.source === 'spp'" class="block text-[11px] text-primary">Otomatis dari pembayaran SPP</span>
                </td>
                <td class="border border-outline-variant p-2">{{ t.category?.name || '—' }}</td>
                <td v-if="isExpense" class="border border-outline-variant p-2">{{ t.destination || '—' }}</td>
                <td class="border border-outline-variant p-2 text-right whitespace-nowrap">{{ formatRupiah(t.amount) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="font-bold bg-surface-container-low">
                <td class="border border-outline-variant p-2 text-right" :colspan="isExpense ? 5 : 4">Total {{ isIncome ? 'Pemasukan' : 'Pengeluaran' }}</td>
                <td class="border border-outline-variant p-2 text-right whitespace-nowrap">{{ formatRupiah(total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <p class="text-xs text-on-surface-variant">
          Jumlah transaksi: <strong>{{ rows.length }}</strong>. Dicetak {{ formatDateTime(new Date().toISOString()) }}.
        </p>
      </template>
    </div>
  </FinanceShell>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FinanceShell from '../../Components/FinanceShell.vue'
import { formatDate, formatDateTime, formatRupiah, periodTag, useFinanceApi } from '../../composables/useFinance.js'
import { unitLabel } from '../../data/lpi.js'

const route = useRoute()
const router = useRouter()
const { loading, error, request } = useFinanceApi()

const isExpense = computed(() => route.meta.reportType === 'expense')
const isIncome = computed(() => !isExpense.value)
const title = computed(() => (isIncome.value ? 'Laporan Pemasukan' : 'Laporan Pengeluaran'))

const rows = ref([])
const total = ref(0)
const unit = ref('')
const unitName = computed(() => unitLabel(unit.value))
const from = ref(route.query.from || '')
const to = ref(route.query.to || '')
const categoryId = route.query.category_id || ''
const search = route.query.search || ''

const periodLabel = computed(() => {
  if (from.value && to.value) return `Periode ${formatDate(from.value)} – ${formatDate(to.value)}`
  if (from.value) return `Sejak ${formatDate(from.value)}`
  if (to.value) return `Sampai ${formatDate(to.value)}`
  return 'Seluruh periode'
})

async function load() {
  const q = new URLSearchParams({ type: isExpense.value ? 'expense' : 'income' })
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  if (categoryId) q.set('category_id', categoryId)
  if (search) q.set('search', search)
  try {
    const res = await request(`/api/finance/transactions/preview?${q}`)
    rows.value = res.transactions || []
    total.value = res.total || 0
    unit.value = res.unit || ''
  } catch {
    rows.value = []
    total.value = 0
  }
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/app/bendahara/laporan')
}

function audit(event) {
  const token = localStorage.getItem('madani_token')
  if (!token) return
  fetch('/api/finance/report-audit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({
      report: 'transactions',
      event,
      type: isExpense.value ? 'expense' : 'income',
      from: from.value || null,
      to: to.value || null,
      category_id: categoryId || null,
      search: search || null,
    }),
  }).catch(() => {})
}

function print() {
  audit('print')
  window.print()
}

function downloadCsv() {
  const header = isExpense.value
    ? ['No', 'Tanggal', 'Keterangan', 'Kategori', 'Tujuan', 'Nominal']
    : ['No', 'Tanggal', 'Keterangan', 'Kategori', 'Nominal']
  const data = rows.value.map((t, index) => {
    const base = [index + 1, String(t.transaction_date).slice(0, 10), t.description, t.category?.name || '', t.destination || '']
    return isExpense.value ? [...base, t.amount] : [base[0], base[1], base[2], base[3], t.amount]
  })
  data.push(isExpense.value
    ? ['', '', '', '', 'Total', total.value]
    : ['', '', '', 'Total', total.value])
  audit('export')
  download(`${isIncome.value ? 'laporan-pemasukan' : 'laporan-pengeluaran'}-${periodTag(from.value, to.value)}.csv`, [header, ...data])
}

function download(name, rowsData) {
  const csv = rowsData.map((r) => r.map((c) => `"${String(c ?? '').replaceAll('"', '""')}"`).join(',')).join('\n')
  const blob = new Blob([`\ufeff${csv}`], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = name
  a.click()
  URL.revokeObjectURL(url)
}

watch(() => route.meta.reportType, load)

onMounted(load)
</script>

<style>
@media print {
  body * { visibility: hidden !important; }
  #preview-print, #preview-print * { visibility: visible !important; }
  #preview-print { position: absolute; inset: 0; margin: 0; box-shadow: none; border: none; border-radius: 0; }
  .no-print { display: none !important; }
}
</style>
