<template>
  <FinanceShell title="Laporan Keuangan" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div v-if="error" class="p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>

    <div class="flex flex-wrap items-center gap-2">
      <button type="button" class="btn btn--ghost btn--sm" @click="openPreview('pemasukan')">
        <span class="material-symbols-outlined !w-4 !h-4">visibility</span>
        Preview Pemasukan
      </button>
      <button type="button" class="btn btn--ghost btn--sm" @click="openPreview('pengeluaran')">
        <span class="material-symbols-outlined !w-4 !h-4">visibility</span>
        Preview Pengeluaran
      </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4">
        <p class="font-label-sm text-label-sm uppercase tracking-wide text-on-surface-variant">Total Pemasukan</p>
        <p class="text-2xl font-black text-success mt-1">{{ formatRupiah(report?.total_income) }}</p>
      </div>
      <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4">
        <p class="font-label-sm text-label-sm uppercase tracking-wide text-on-surface-variant">Total Pengeluaran</p>
        <p class="text-2xl font-black text-error mt-1">{{ formatRupiah(report?.total_expense) }}</p>
      </div>
      <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4">
        <p class="font-label-sm text-label-sm uppercase tracking-wide text-on-surface-variant">Selisih</p>
        <p class="text-2xl font-black text-primary mt-1">{{ formatRupiah((report?.total_income || 0) - (report?.total_expense || 0)) }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
      <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
        <div class="flex items-center justify-between gap-2 mb-3">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Per Kategori</h2>
          <button type="button" class="btn btn--ghost btn--sm" @click="exportCategories">CSV</button>
        </div>
        <div v-if="!report?.by_category?.length" class="text-sm text-on-surface-variant py-6 text-center">Belum ada data.</div>
        <table v-else class="w-full text-sm">
          <thead class="text-on-surface-variant">
            <tr class="border-b border-outline-variant">
              <th class="text-left p-1.5">Kategori</th>
              <th class="text-left p-1.5">Jenis</th>
              <th class="text-right p-1.5">Jumlah</th>
              <th class="text-right p-1.5">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in report.by_category" :key="row.type + row.category" class="border-b border-outline-variant/60">
              <td class="p-1.5">{{ row.category }}</td>
              <td class="p-1.5"><span class="badge" :class="row.type === 'income' ? 'bg-success-bg text-success' : 'bg-error-bg text-error'">{{ row.type === 'income' ? 'Masuk' : 'Keluar' }}</span></td>
              <td class="p-1.5 text-right">{{ row.count }}</td>
              <td class="p-1.5 text-right font-bold">{{ formatRupiah(row.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
        <div class="flex items-center justify-between gap-2 mb-3">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Rekap Harian</h2>
          <button type="button" class="btn btn--ghost btn--sm" @click="exportDaily">CSV</button>
        </div>
        <div v-if="!report?.by_day?.length" class="text-sm text-on-surface-variant py-6 text-center">Belum ada data.</div>
        <div v-else class="max-h-80 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="text-on-surface-variant sticky top-0 bg-white">
              <tr class="border-b border-outline-variant">
                <th class="text-left p-1.5">Tanggal</th>
                <th class="text-right p-1.5">Masuk</th>
                <th class="text-right p-1.5">Keluar</th>
                <th class="text-right p-1.5">Selisih</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in report.by_day" :key="d.date" class="border-b border-outline-variant/60">
                <td class="p-1.5 whitespace-nowrap">{{ formatDate(d.date) }}</td>
                <td class="p-1.5 text-right text-success">{{ formatRupiah(d.income) }}</td>
                <td class="p-1.5 text-right text-error">{{ formatRupiah(d.expense) }}</td>
                <td class="p-1.5 text-right font-bold">{{ formatRupiah(d.income - d.expense) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4">
      <div class="flex items-center justify-between gap-2 mb-3">
        <h2 class="font-headline-sm text-headline-sm font-semibold">Rekap SPP</h2>
        <button type="button" class="btn btn--ghost btn--sm" @click="exportSpp">CSV</button>
      </div>
      <div v-if="!spp" class="text-sm text-on-surface-variant py-4 text-center">Memuat rekap SPP…</div>
      <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
        <div class="bg-surface-container-low rounded-lg p-3">
          <p class="text-[11px] uppercase tracking-wide text-on-surface-variant">Total Tagihan</p>
          <p class="font-bold mt-1">{{ formatRupiah(spp.invoice_total) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-lg p-3">
          <p class="text-[11px] uppercase tracking-wide text-on-surface-variant">Sudah Dibayar</p>
          <p class="font-bold mt-1 text-success">{{ formatRupiah(spp.invoice_paid) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-lg p-3">
          <p class="text-[11px] uppercase tracking-wide text-on-surface-variant">Tunggakan</p>
          <p class="font-bold mt-1 text-error">{{ formatRupiah(spp.outstanding) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-lg p-3">
          <p class="text-[11px] uppercase tracking-wide text-on-surface-variant">Pembayaran</p>
          <p class="font-bold mt-1">{{ spp.payments_count }} transaksi</p>
        </div>
        <div class="col-span-2 lg:col-span-4 flex flex-wrap gap-2 text-xs">
          <span class="badge bg-error-bg text-error">Belum bayar: {{ spp.invoices_by_status?.unpaid ?? 0 }}</span>
          <span class="badge bg-warning-bg text-warning">Sebagian: {{ spp.invoices_by_status?.partial ?? 0 }}</span>
          <span class="badge bg-success-bg text-success">Lunas: {{ spp.invoices_by_status?.paid ?? 0 }}</span>
        </div>
      </div>
    </div>
  </FinanceShell>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import FinanceShell from '../../Components/FinanceShell.vue'
import { formatDate, formatRupiah, periodTag, useFinanceApi } from '../../composables/useFinance.js'

const router = useRouter()
const { error, request } = useFinanceApi()
const report = ref(null)
const spp = ref(null)
const from = ref('')
const to = ref('')

function query() {
  const q = new URLSearchParams()
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  const s = q.toString()
  return s ? `?${s}` : ''
}

async function load() {
  const qs = query()
  try {
    report.value = await request(`/api/finance/report${qs}`)
    spp.value = await request(`/api/finance/spp/report${qs}`)
  } catch { /* error ditampilkan */ }
}

function openPreview(kind) {
  const q = {}
  if (from.value) q.from = from.value
  if (to.value) q.to = to.value
  router.push({ path: `/app/bendahara/preview/${kind}`, query: q })
}

function audit(report) {
  const token = localStorage.getItem('madani_token')
  if (!token) return
  fetch('/api/finance/report-audit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({
      report,
      event: 'export',
      from: from.value || null,
      to: to.value || null,
    }),
  }).catch(() => {})
}

function download(name, rows) {
  const csv = rows.map((r) => r.map((c) => `"${String(c ?? '').replaceAll('"', '""')}"`).join(',')).join('\n')
  const blob = new Blob([`\ufeff${csv}`], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = name
  a.click()
  URL.revokeObjectURL(url)
}

function exportCategories() {
  const rows = [['Kategori', 'Jenis', 'Jumlah Transaksi', 'Total']]
  for (const r of report.value?.by_category || []) {
    rows.push([r.category, r.type === 'income' ? 'Pemasukan' : 'Pengeluaran', r.count, r.total])
  }
  audit('transactions')
  download(`laporan-kategori-${periodTag(from.value, to.value)}.csv`, rows)
}

function exportDaily() {
  const rows = [['Tanggal', 'Pemasukan', 'Pengeluaran', 'Selisih']]
  for (const d of report.value?.by_day || []) {
    rows.push([d.date, d.income, d.expense, d.income - d.expense])
  }
  audit('transactions')
  download(`laporan-harian-${periodTag(from.value, to.value)}.csv`, rows)
}

function exportSpp() {
  const s = spp.value || {}
  audit('spp')
  download(`rekap-spp-${periodTag(from.value, to.value)}.csv`, [
    ['Rekap SPP', 'Nilai'],
    ['Total Tagihan', s.invoice_total ?? 0],
    ['Sudah Dibayar', s.invoice_paid ?? 0],
    ['Tunggakan', s.outstanding ?? 0],
    ['Jumlah Pembayaran', s.payments_count ?? 0],
    ['Tagihan Belum Bayar', s.invoices_by_status?.unpaid ?? 0],
    ['Tagihan Sebagian', s.invoices_by_status?.partial ?? 0],
    ['Tagihan Lunas', s.invoices_by_status?.paid ?? 0],
  ])
}

onMounted(load)
</script>
