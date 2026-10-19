<template>
  <FinanceShell title="Dashboard Keuangan" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div v-if="error" class="p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>
    <div v-if="loading && !summary" class="p-8 text-center text-sm text-on-surface-variant">Memuat data keuangan…</div>

    <template v-if="summary">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="card in cards" :key="card.label" class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <div class="flex items-center gap-2 text-on-surface-variant">
            <span class="material-symbols-outlined !w-4 !h-4">{{ card.icon }}</span>
            <span class="font-label-sm text-label-sm uppercase tracking-wide">{{ card.label }}</span>
          </div>
          <div class="mt-2 text-xl md:text-2xl font-black break-words" :class="card.class">{{ card.value }}</div>
          <div v-if="card.hint" class="text-[11px] text-on-surface-variant mt-1">{{ card.hint }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <div class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined !w-4 !h-4">receipt_long</span><span class="font-label-sm text-label-sm uppercase tracking-wide">Tagihan SPP</span></div>
          <div class="mt-2 text-2xl font-black">{{ formatRupiah(summary.spp_invoice_total) }}</div>
          <p class="text-[11px] text-on-surface-variant mt-1">{{ summary.spp_invoice_count }} tagihan · {{ summary.spp_unpaid_count }} belum lunas</p>
        </div>
        <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <div class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined !w-4 !h-4">payments</span><span class="font-label-sm text-label-sm uppercase tracking-wide">SPP Diterima</span></div>
          <div class="mt-2 text-2xl font-black text-success">{{ formatRupiah(summary.spp_received) }}</div>
          <p class="text-[11px] text-on-surface-variant mt-1">Pembayaran tercatat pada periode ini</p>
        </div>
        <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <div class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined !w-4 !h-4">warning</span><span class="font-label-sm text-label-sm uppercase tracking-wide">Tunggakan</span></div>
          <div class="mt-2 text-2xl font-black text-error">{{ formatRupiah(summary.spp_outstanding) }}</div>
          <p class="text-[11px] text-on-surface-variant mt-1">Sisa tagihan yang belum dibayar</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <h2 class="font-headline-sm text-headline-sm font-semibold mb-3 flex items-center gap-2"><span class="material-symbols-outlined !w-4 !h-4 text-primary">bar_chart</span> Grafik Bulanan</h2>
          <div v-if="!summary.trend.length" class="text-sm text-on-surface-variant py-6 text-center">Belum ada transaksi pada periode ini.</div>
          <div v-else class="space-y-2">
            <div v-for="t in summary.trend" :key="t.period" class="grid grid-cols-[70px_1fr] gap-2 items-center">
              <span class="text-[11px] font-bold text-on-surface-variant">{{ monthLabel(t.period) }}</span>
              <div class="space-y-1">
                <div class="h-2 rounded-full bg-surface-container overflow-hidden">
                  <div class="h-full bg-success rounded-full" :style="{ width: barWidth(t.income) }"></div>
                </div>
                <div class="h-2 rounded-full bg-surface-container overflow-hidden">
                  <div class="h-full bg-error rounded-full" :style="{ width: barWidth(t.expense) }"></div>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-4 pt-2 text-[11px] text-on-surface-variant">
              <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-success"></span> Pemasukan</span>
              <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-error"></span> Pengeluaran</span>
            </div>
          </div>
        </div>

        <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 min-w-0">
          <h2 class="font-headline-sm text-headline-sm font-semibold mb-3 flex items-center gap-2"><span class="material-symbols-outlined !w-4 !h-4 text-primary">history</span> Transaksi Terbaru</h2>
          <div v-if="!summary.recent_transactions.length" class="text-sm text-on-surface-variant py-6 text-center">Belum ada transaksi.</div>
          <ul v-else class="divide-y divide-outline-variant">
            <li v-for="t in summary.recent_transactions" :key="t.id" class="py-2 flex items-center justify-between gap-2 min-w-0">
              <div class="min-w-0">
                <p class="text-sm font-semibold truncate">{{ t.description }}</p>
                <p class="text-[11px] text-on-surface-variant">{{ formatDate(t.transaction_date) }} · {{ t.category?.name || 'Tanpa kategori' }}</p>
              </div>
              <span class="text-sm font-bold shrink-0" :class="t.type === 'income' ? 'text-success' : 'text-error'">
                {{ t.type === 'income' ? '+' : '−' }} {{ formatRupiah(t.amount) }}
              </span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </FinanceShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import FinanceShell from '../../Components/FinanceShell.vue'
import { formatDate, formatRupiah, monthLabel, useFinanceApi } from '../../composables/useFinance.js'

const { loading, error, request } = useFinanceApi()
const summary = ref(null)
const from = ref('')
const to = ref('')

const cards = computed(() => {
  if (!summary.value) return []
  return [
    { label: 'Saldo Awal', value: formatRupiah(summary.value.opening_balance), icon: 'savings', hint: 'Saldo pembukaan unit', class: 'text-on-surface' },
    { label: 'Total Pemasukan', value: formatRupiah(summary.value.total_income), icon: 'trending_up', hint: 'Termasuk pembayaran SPP', class: 'text-success' },
    { label: 'Total Pengeluaran', value: formatRupiah(summary.value.total_expense), icon: 'trending_down', hint: 'Seluruh pengeluaran unit', class: 'text-error' },
    { label: 'Saldo', value: formatRupiah(summary.value.balance), icon: 'account_balance_wallet', hint: 'Saldo awal + pemasukan − pengeluaran', class: 'text-primary' },
  ]
})

const maxTrend = computed(() => {
  if (!summary.value?.trend?.length) return 1
  return Math.max(1, ...summary.value.trend.flatMap((t) => [Number(t.income), Number(t.expense)]))
})

function barWidth(v) {
  return Math.max(2, Math.round((Number(v) / maxTrend.value) * 100)) + '%'
}

async function load() {
  const q = new URLSearchParams()
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  const qs = q.toString()
  summary.value = await request(`/api/finance/dashboard${qs ? '?' + qs : ''}`).catch(() => summary.value)
}

onMounted(load)
</script>
