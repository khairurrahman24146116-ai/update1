<template>
  <FinanceShell title="Pembayaran SPP" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div v-if="error" class="p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-4 space-y-3">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[220px]">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">search</span>
          <input v-model="studentQuery" type="search" class="input pl-10" placeholder="Cari siswa: nama, NIS, atau NISN" @input="searchStudents" />
        </div>
        <select v-model="statusFilter" class="input w-44" @change="goPage(1)">
          <option value="">Semua status</option>
          <option value="unpaid">Belum dibayar</option>
          <option value="partial">Sebagian</option>
          <option value="paid">Lunas</option>
        </select>
        <input v-model="periodFilter" type="month" class="input w-44" @change="goPage(1)" />
        <button type="button" class="btn btn--ghost btn--sm" @click="openGenerate">Generate Tagihan</button>
        <button type="button" class="btn btn--primary btn--sm" @click="openInvoiceForm">+ Tagihan</button>
      </div>
      <div v-if="studentResults.length" class="border border-outline-variant rounded-lg divide-y divide-outline-variant max-h-64 overflow-y-auto">
        <button v-for="s in studentResults" :key="s.id" type="button" class="w-full text-left p-2 hover:bg-surface-container-low flex items-center justify-between gap-2" @click="selectStudent(s)">
          <span class="min-w-0">
            <span class="block text-sm font-semibold truncate">{{ s.name }}</span>
            <span class="block text-[11px] text-on-surface-variant">{{ s.nis }} · {{ s.nisn || 'NISN —' }} · {{ s.classroom?.name }}</span>
          </span>
          <span class="material-symbols-outlined !w-4 !h-4 text-primary">chevron_right</span>
        </button>
      </div>
      <p v-if="selectedStudent" class="text-xs text-on-surface-variant">
        Menampilkan tagihan untuk <strong>{{ selectedStudent.name }}</strong> ({{ selectedStudent.nis }}).
        <button type="button" class="underline ml-1" @click="clearStudent">Tampilkan semua</button>
      </p>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-x-auto">
      <div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat…</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-primary text-on-primary">
          <tr>
            <th class="p-2 text-left">Siswa</th>
            <th class="p-2 text-left">Periode</th>
            <th class="p-2 text-right">Tagihan</th>
            <th class="p-2 text-right">Terbayar</th>
            <th class="p-2 text-right">Sisa</th>
            <th class="p-2 text-left">Status</th>
            <th class="p-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in list" :key="inv.id" class="border-t">
            <td class="p-2 min-w-[180px]">
              <div class="font-semibold">{{ inv.student?.name }}</div>
              <div class="text-[11px] text-on-surface-variant">{{ inv.student?.nis }} · {{ inv.student?.classroom?.name }}</div>
            </td>
            <td class="p-2 whitespace-nowrap">{{ monthLabel(inv.period) }}</td>
            <td class="p-2 text-right whitespace-nowrap">{{ formatRupiah(inv.amount) }}</td>
            <td class="p-2 text-right whitespace-nowrap">{{ formatRupiah(inv.paid_amount) }}</td>
            <td class="p-2 text-right whitespace-nowrap font-bold" :class="remaining(inv) > 0 ? 'text-error' : 'text-success'">{{ formatRupiah(remaining(inv)) }}</td>
            <td class="p-2"><span class="badge" :class="statusClass(inv.status)">{{ statusLabel(inv.status) }}</span></td>
            <td class="p-2 text-right whitespace-nowrap">
              <button type="button" class="text-xs underline" @click="openDetail(inv)">Detail</button>
              <button v-if="inv.status !== 'paid'" type="button" class="text-xs underline ml-2 text-primary" @click="openPay(inv)">Bayar</button>
            </td>
          </tr>
        </tbody>
      </table>
      <EmptyState v-if="!loading && !list.length" icon="receipt_long" title="Belum ada tagihan" description="Buat tagihan per siswa atau generate untuk seluruh siswa unit ini." />
      <div v-if="meta" class="flex items-center justify-between gap-2 p-3 border-t text-xs">
        <span class="text-on-surface-variant">Total {{ meta.total }} · Hal {{ meta.current_page }}/{{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">Prev</button>
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">Next</button>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="showPay" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="showPay = false">
        <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-md p-5 space-y-3">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Pembayaran SPP</h2>
          <div class="text-sm space-y-1 bg-surface-container-low rounded-lg p-3">
            <p class="font-bold">{{ payTarget?.student?.name }}</p>
            <p class="text-on-surface-variant">Periode {{ monthLabel(payTarget?.period) }} · Tagihan {{ formatRupiah(payTarget?.amount) }}</p>
            <p class="text-on-surface-variant">Sisa: <strong class="text-error">{{ formatRupiah(remaining(payTarget)) }}</strong></p>
          </div>
          <div class="grid grid-cols-1 gap-3">
            <div class="space-y-1">
              <label class="font-label-sm text-label-sm font-bold">Nominal bayar *</label>
              <input v-model="payForm.amount" type="number" min="1" step="0.01" class="input" />
            </div>
            <div class="space-y-1">
              <label class="font-label-sm text-label-sm font-bold">Tanggal bayar</label>
              <input v-model="payForm.paid_at" type="datetime-local" class="input" />
            </div>
            <div class="space-y-1">
              <label class="font-label-sm text-label-sm font-bold">Metode</label>
              <select v-model="payForm.method" class="input">
                <option value="tunai">Tunai</option>
                <option value="transfer">Transfer</option>
                <option value="qris">QRIS</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="font-label-sm text-label-sm font-bold">Catatan</label>
              <input v-model="payForm.notes" class="input" />
            </div>
            <div class="space-y-1">
              <label class="font-label-sm text-label-sm font-bold">Bukti (opsional)</label>
              <input ref="payFile" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="input py-2" />
            </div>
          </div>
          <p v-if="payError" class="text-xs text-error">{{ payError }}</p>
          <div class="flex gap-2">
            <button type="button" class="btn btn--primary btn--sm" :disabled="paying" @click="submitPay">{{ paying ? 'Menyimpan…' : 'Simpan Pembayaran' }}</button>
            <button type="button" class="btn btn--ghost btn--sm" @click="showPay = false">Batal</button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="showInvoiceForm" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="showInvoiceForm = false">
        <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-md p-5 space-y-3">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Tagihan Baru</h2>
          <p v-if="!invoiceStudent" class="text-xs text-on-surface-variant">Pilih siswa terlebih dahulu melalui pencarian di halaman ini.</p>
          <template v-else>
            <p class="text-sm">Siswa: <strong>{{ invoiceStudent.name }}</strong> ({{ invoiceStudent.nis }})</p>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1"><label class="font-label-sm text-label-sm font-bold">Periode *</label><input v-model="invoiceForm.period" type="month" class="input" /></div>
              <div class="space-y-1"><label class="font-label-sm text-label-sm font-bold">Nominal *</label><input v-model="invoiceForm.amount" type="number" min="1" class="input" /></div>
              <div class="space-y-1 col-span-2"><label class="font-label-sm text-label-sm font-bold">Jatuh tempo</label><input v-model="invoiceForm.due_date" type="date" class="input" /></div>
            </div>
            <p v-if="invoiceError" class="text-xs text-error">{{ invoiceError }}</p>
            <div class="flex gap-2">
              <button type="button" class="btn btn--primary btn--sm" @click="submitInvoice">Simpan</button>
              <button type="button" class="btn btn--ghost btn--sm" @click="showInvoiceForm = false">Batal</button>
            </div>
          </template>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="showGenerate" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="showGenerate = false">
        <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-md p-5 space-y-3">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Generate Tagihan Unit</h2>
          <p class="text-xs text-on-surface-variant">Membuat tagihan untuk semua siswa aktif di unit ini. Tagihan yang sudah ada dilewati.</p>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1"><label class="font-label-sm text-label-sm font-bold">Periode *</label><input v-model="generateForm.period" type="month" class="input" /></div>
            <div class="space-y-1"><label class="font-label-sm text-label-sm font-bold">Nominal *</label><input v-model="generateForm.amount" type="number" min="1" class="input" /></div>
            <div class="space-y-1 col-span-2"><label class="font-label-sm text-label-sm font-bold">Jatuh tempo</label><input v-model="generateForm.due_date" type="date" class="input" /></div>
          </div>
          <p v-if="generateMsg" class="text-xs font-bold text-success">{{ generateMsg }}</p>
          <div class="flex gap-2">
            <button type="button" class="btn btn--primary btn--sm" @click="submitGenerate">Generate</button>
            <button type="button" class="btn btn--ghost btn--sm" @click="showGenerate = false">Tutup</button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="detail" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="detail = null">
        <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-lg p-5 space-y-3 max-h-[85vh] overflow-y-auto">
          <div class="flex items-center justify-between">
            <h2 class="font-headline-sm text-headline-sm font-semibold">Detail Tagihan</h2>
            <button type="button" class="btn btn--ghost btn--sm" @click="detail = null">Tutup</button>
          </div>
          <div class="text-sm space-y-1">
            <p class="font-bold">{{ detail.student?.name }} · {{ detail.student?.nis }}</p>
            <p class="text-on-surface-variant">{{ monthLabel(detail.period) }} · {{ statusLabel(detail.status) }}</p>
            <p>Tagihan {{ formatRupiah(detail.amount) }} · Terbayar {{ formatRupiah(detail.paid_amount) }} · Sisa {{ formatRupiah(remaining(detail)) }}</p>
          </div>
          <div>
            <h3 class="font-bold text-sm mb-2">Riwayat Pembayaran</h3>
            <div v-if="!detail.payments?.length" class="text-xs text-on-surface-variant">Belum ada pembayaran.</div>
            <ul v-else class="divide-y divide-outline-variant text-sm">
              <li v-for="p in detail.payments" :key="p.id" class="py-2 flex items-center justify-between gap-2">
                <div>
                  <p class="font-semibold">{{ p.receipt_number }}</p>
                  <p class="text-[11px] text-on-surface-variant">{{ formatDateTime(p.paid_at) }} · {{ p.method }} · {{ p.receiver?.name || '—' }}</p>
                </div>
                <span class="font-bold text-success">{{ formatRupiah(p.amount) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </Transition>
  </FinanceShell>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import FinanceShell from '../../Components/FinanceShell.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { currentPeriod, formatDateTime, formatRupiah, monthLabel, useFinanceApi } from '../../composables/useFinance.js'

const { loading, error, request } = useFinanceApi()

const list = ref([])
const meta = ref(null)
const page = ref(1)
const statusFilter = ref('')
const periodFilter = ref('')
const from = ref('')
const to = ref('')
const studentQuery = ref('')
const studentResults = ref([])
const selectedStudent = ref(null)
let searchTimer = null

const showPay = ref(false)
const payTarget = ref(null)
const paying = ref(false)
const payError = ref('')
const payFile = ref(null)
const payForm = ref({ amount: '', paid_at: '', method: 'tunai', notes: '' })

const showInvoiceForm = ref(false)
const invoiceStudent = ref(null)
const invoiceForm = ref({ period: currentPeriod(), amount: '', due_date: '' })
const invoiceError = ref('')

const showGenerate = ref(false)
const generateForm = ref({ period: currentPeriod(), amount: '', due_date: '' })
const generateMsg = ref('')

const detail = ref(null)

function remaining(inv) {
  if (!inv) return 0
  return Math.max(0, Number(inv.amount || 0) - Number(inv.paid_amount || 0))
}

function statusLabel(s) {
  return { paid: 'Lunas', partial: 'Sebagian', unpaid: 'Belum bayar' }[s] || s
}

function statusClass(s) {
  return { paid: 'bg-success-bg text-success', partial: 'bg-warning-bg text-warning', unpaid: 'bg-error-bg text-error' }[s] || ''
}

function searchStudents() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    if (!studentQuery.value.trim()) {
      studentResults.value = []
      return
    }
    try {
      studentResults.value = await request(`/api/finance/spp/students?search=${encodeURIComponent(studentQuery.value.trim())}`)
    } catch { studentResults.value = [] }
  }, 300)
}

function selectStudent(s) {
  selectedStudent.value = s
  studentResults.value = []
  studentQuery.value = s.name
  goPage(1)
}

function clearStudent() {
  selectedStudent.value = null
  studentQuery.value = ''
  goPage(1)
}

function goPage(p) {
  page.value = Math.max(1, p)
  load()
}

async function load() {
  const q = new URLSearchParams({ page: String(page.value), per_page: '20' })
  if (statusFilter.value) q.set('status', statusFilter.value)
  if (periodFilter.value) q.set('period', periodFilter.value)
  if (selectedStudent.value) q.set('student_id', selectedStudent.value.id)
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  try {
    const res = await request(`/api/finance/spp/invoices?${q}`)
    list.value = res.data || []
    meta.value = { total: res.total, current_page: res.current_page, last_page: res.last_page }
  } catch { /* error ditampilkan */ }
}

function openPay(inv) {
  payTarget.value = inv
  payError.value = ''
  payForm.value = {
    amount: String(remaining(inv)),
    paid_at: new Date().toISOString().slice(0, 16),
    method: 'tunai',
    notes: '',
  }
  showPay.value = true
}

async function submitPay() {
  paying.value = true
  payError.value = ''
  try {
    const fd = new FormData()
    fd.append('amount', payForm.value.amount)
    if (payForm.value.paid_at) fd.append('paid_at', payForm.value.paid_at.replace('T', ' '))
    fd.append('method', payForm.value.method)
    if (payForm.value.notes) fd.append('notes', payForm.value.notes)
    if (payFile.value?.files?.[0]) fd.append('proof', payFile.value.files[0])
    await request(`/api/finance/spp/invoices/${payTarget.value.id}/payments`, { method: 'POST', body: fd })
    showPay.value = false
    await load()
  } catch (e) {
    payError.value = e.message
  } finally {
    paying.value = false
  }
}

function openInvoiceForm() {
  invoiceStudent.value = selectedStudent.value
  invoiceForm.value = { period: currentPeriod(), amount: '', due_date: '' }
  invoiceError.value = ''
  showInvoiceForm.value = true
}

async function submitInvoice() {
  invoiceError.value = ''
  try {
    await request('/api/finance/spp/invoices', {
      method: 'POST',
      body: {
        student_id: invoiceStudent.value.id,
        period: invoiceForm.value.period,
        amount: invoiceForm.value.amount,
        due_date: invoiceForm.value.due_date || null,
      },
    })
    showInvoiceForm.value = false
    await load()
  } catch (e) {
    invoiceError.value = e.message
  }
}

function openGenerate() {
  generateForm.value = { period: currentPeriod(), amount: '', due_date: '' }
  generateMsg.value = ''
  showGenerate.value = true
}

async function submitGenerate() {
  generateMsg.value = ''
  try {
    const res = await request('/api/finance/spp/invoices/generate', { method: 'POST', body: generateForm.value })
    generateMsg.value = `${res.created} tagihan baru dibuat.`
    await load()
  } catch (e) {
    generateMsg.value = e.message
  }
}

async function openDetail(inv) {
  try {
    detail.value = await request(`/api/finance/spp/invoices/${inv.id}`)
  } catch { /* error ditampilkan */ }
}

onMounted(load)
</script>
