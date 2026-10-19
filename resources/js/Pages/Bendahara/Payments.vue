<template>
  <FinanceShell title="Riwayat Pembayaran SPP" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div v-if="error" class="p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 flex flex-wrap items-center gap-2">
      <input v-model="search" type="search" class="input w-64" placeholder="Cari kuitansi / nama / NIS" @input="onSearch" />
      <input v-model="period" type="month" class="input w-44" @change="goPage(1)" />
      <select v-model="method" class="input w-40" @change="goPage(1)">
        <option value="">Semua metode</option>
        <option value="tunai">Tunai</option>
        <option value="transfer">Transfer</option>
        <option value="qris">QRIS</option>
        <option value="lainnya">Lainnya</option>
      </select>
      <span class="text-xs text-on-surface-variant ml-auto">Total halaman: {{ meta?.total ?? 0 }} pembayaran</span>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-x-auto">
      <div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat…</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-primary text-on-primary">
          <tr>
            <th class="p-2 text-left">Kuitansi</th>
            <th class="p-2 text-left">Siswa</th>
            <th class="p-2 text-left">Periode</th>
            <th class="p-2 text-left">Tanggal Bayar</th>
            <th class="p-2 text-left">Metode</th>
            <th class="p-2 text-left">Penerima</th>
            <th class="p-2 text-right">Nominal</th>
            <th class="p-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in list" :key="p.id" class="border-t">
            <td class="p-2 font-mono text-xs whitespace-nowrap">{{ p.receipt_number }}</td>
            <td class="p-2 min-w-[180px]">
              <div class="font-semibold">{{ p.invoice?.student?.name }}</div>
              <div class="text-[11px] text-on-surface-variant">{{ p.invoice?.student?.nis }}</div>
            </td>
            <td class="p-2 whitespace-nowrap">{{ monthLabel(p.invoice?.period) }}</td>
            <td class="p-2 whitespace-nowrap">{{ formatDateTime(p.paid_at) }}</td>
            <td class="p-2 capitalize">{{ p.method }}</td>
            <td class="p-2">{{ p.receiver?.name || '—' }}</td>
            <td class="p-2 text-right font-bold text-success whitespace-nowrap">{{ formatRupiah(p.amount) }}</td>
            <td class="p-2 text-right whitespace-nowrap">
              <button type="button" class="text-xs underline" @click="openReceipt(p)">Kuitansi</button>
              <button v-if="isAdmin" type="button" class="text-xs underline ml-2 text-error" @click="cancel(p)">Batalkan</button>
            </td>
          </tr>
        </tbody>
      </table>
      <EmptyState v-if="!loading && !list.length" icon="history" title="Belum ada pembayaran" description="Pembayaran SPP yang tercatat akan tampil di sini." />
      <div v-if="meta" class="flex items-center justify-between gap-2 p-3 border-t text-xs">
        <span class="text-on-surface-variant">Total {{ meta.total }} · Hal {{ meta.current_page }}/{{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">Prev</button>
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">Next</button>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="receipt" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="receipt = null">
        <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-md p-6 space-y-4" id="receipt-print">
          <div class="text-center border-b border-outline-variant pb-3">
            <p class="font-headline-sm text-headline-sm font-bold uppercase tracking-wide">Kuitansi Pembayaran SPP</p>
            <p class="text-xs text-on-surface-variant">{{ receiptUnitLabel }}</p>
          </div>
          <div class="text-sm space-y-1.5">
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">No. Kuitansi</span><span class="font-mono font-bold">{{ receipt.receipt_number }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">Nama</span><span class="font-semibold">{{ receipt.invoice?.student?.name }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">NIS</span><span>{{ receipt.invoice?.student?.nis }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">Kelas</span><span>{{ receipt.invoice?.student?.classroom?.name || '—' }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">Periode</span><span>{{ monthLabel(receipt.invoice?.period) }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">Tanggal</span><span>{{ formatDateTime(receipt.paid_at) }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-on-surface-variant">Metode</span><span class="capitalize">{{ receipt.method }}</span></div>
            <div class="flex justify-between gap-3 border-t border-outline-variant pt-2 mt-2"><span class="font-bold">Jumlah</span><span class="font-black text-lg text-success">{{ formatRupiah(receipt.amount) }}</span></div>
            <div v-if="receipt.notes" class="flex justify-between gap-3"><span class="text-on-surface-variant">Catatan</span><span>{{ receipt.notes }}</span></div>
          </div>
          <p class="text-[11px] text-on-surface-variant text-center">Diterima oleh {{ receipt.receiver?.name || '—' }}</p>
          <div class="flex gap-2 no-print">
            <button type="button" class="btn btn--primary btn--sm" @click="printReceipt">Cetak</button>
            <button type="button" class="btn btn--ghost btn--sm" @click="receipt = null">Tutup</button>
          </div>
        </div>
      </div>
    </Transition>
  </FinanceShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import FinanceShell from '../../Components/FinanceShell.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { formatDateTime, formatRupiah, monthLabel, useFinanceApi } from '../../composables/useFinance.js'
import { useAuth } from '../../composables/useAuth.js'
import { unitLabel } from '../../data/lpi.js'

const { loading, error, request } = useFinanceApi()
const { user } = useAuth()
const isAdmin = computed(() => user.value?.role === 'admin')

const list = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const period = ref('')
const method = ref('')
const from = ref('')
const to = ref('')
const receipt = ref(null)
const receiptUnitLabel = computed(() => unitLabel(receipt.value?.unit))
let timer = null

function onSearch() {
  clearTimeout(timer)
  timer = setTimeout(() => goPage(1), 350)
}

function goPage(p) {
  page.value = Math.max(1, p)
  load()
}

async function load() {
  const q = new URLSearchParams({ page: String(page.value), per_page: '20' })
  if (search.value) q.set('search', search.value)
  if (period.value) q.set('period', period.value)
  if (method.value) q.set('method', method.value)
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  try {
    const res = await request(`/api/finance/spp/payments?${q}`)
    list.value = res.data || []
    meta.value = { total: res.total, current_page: res.current_page, last_page: res.last_page }
  } catch { /* error ditampilkan */ }
}

function openReceipt(p) {
  receipt.value = p
}

function printReceipt() {
  window.print()
}

async function cancel(p) {
  if (!confirm(`Batalkan pembayaran ${p.receipt_number}? Tindakan ini menghapus pemasukan terkait.`)) return
  try {
    await request(`/api/finance/spp/payments/${p.id}`, { method: 'DELETE' })
    await load()
  } catch { /* error ditampilkan */ }
}

onMounted(load)
</script>

<style>
@media print {
  body * { visibility: hidden !important; }
  #receipt-print, #receipt-print * { visibility: visible !important; }
  #receipt-print { position: fixed; inset: 0; margin: auto; box-shadow: none; border: none; }
  .no-print { display: none !important; }
}
</style>
