<template>
  <FinanceShell :title="isIncome ? 'Pemasukan Unit' : 'Pengeluaran Unit'" :from="from" :to="to" show-period @update:from="from = $event" @update:to="to = $event" @range="load">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="search" type="search" placeholder="Cari keterangan / referensi" class="input w-64" @input="onSearch" />
        <select v-model="categoryFilter" class="input w-48" @change="load">
          <option value="">Semua kategori</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn btn--ghost btn--sm" @click="openPreview">
          <span class="material-symbols-outlined !w-4 !h-4">visibility</span>
          Preview
        </button>
        <button type="button" class="btn btn--primary btn--sm" @click="openCreate">
          <span class="material-symbols-outlined !w-4 !h-4">add</span>
          Tambah {{ isIncome ? 'Pemasukan' : 'Pengeluaran' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="p-3 bg-error-container border border-error rounded-xl text-sm text-on-error-container">{{ error }}</div>

    <Transition name="fade">
      <div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="font-label-sm text-label-sm font-bold">Tanggal *</label>
            <input v-model="form.transaction_date" type="date" class="input" />
          </div>
          <div class="space-y-1">
            <label class="font-label-sm text-label-sm font-bold">Nominal (Rp) *</label>
            <CurrencyInput v-model="form.amount" class="input" placeholder="0" />
          </div>
          <div class="space-y-1">
            <label class="font-label-sm text-label-sm font-bold">Kategori</label>
            <select v-model="form.category_id" class="input">
              <option value="">Tanpa kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div v-if="!isIncome" class="space-y-1">
            <label class="font-label-sm text-label-sm font-bold">Tujuan / Penerima</label>
            <input v-model="form.destination" class="input" placeholder="Contoh: Toko ATK, vendor listrik" />
          </div>
          <div class="space-y-1 md:col-span-2">
            <label class="font-label-sm text-label-sm font-bold">Keterangan *</label>
            <input v-model="form.description" class="input" placeholder="Uraian transaksi" />
          </div>
          <div class="space-y-1 md:col-span-2">
            <label class="font-label-sm text-label-sm font-bold">Bukti transaksi (opsional)</label>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="input py-2" />
          </div>
        </div>
        <div class="flex gap-2">
          <button type="button" class="btn btn--primary btn--sm" :disabled="saving" @click="submit">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
          <button type="button" class="btn btn--ghost btn--sm" @click="closeForm">Batal</button>
          <button type="button" class="btn btn--ghost btn--sm" @click="addCategory">+ Kategori baru</button>
        </div>
      </div>
    </Transition>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-x-auto">
      <div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat…</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-primary text-on-primary">
          <tr>
            <th class="p-2 text-left">Tanggal</th>
            <th class="p-2 text-left">Keterangan</th>
            <th class="p-2 text-left">Kategori</th>
            <th class="p-2 text-left" v-if="!isIncome">Tujuan</th>
            <th class="p-2 text-right">Nominal</th>
            <th class="p-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in list" :key="t.id" class="border-t align-top">
            <td class="p-2 whitespace-nowrap">{{ formatDate(t.transaction_date) }}</td>
            <td class="p-2 min-w-[200px]">
              <div class="font-semibold">{{ t.description }}</div>
              <div v-if="t.source === 'spp'" class="text-[11px] text-primary">Otomatis dari pembayaran SPP</div>
              <a v-if="t.proof_path" :href="`/storage/${t.proof_path}`" target="_blank" rel="noopener" class="text-[11px] underline text-primary">Lihat bukti</a>
            </td>
            <td class="p-2">{{ t.category?.name || '—' }}</td>
            <td v-if="!isIncome" class="p-2">{{ t.destination || '—' }}</td>
            <td class="p-2 text-right font-bold whitespace-nowrap" :class="isIncome ? 'text-success' : 'text-error'">{{ formatRupiah(t.amount) }}</td>
            <td class="p-2 text-right whitespace-nowrap">
              <button type="button" class="text-xs underline" @click="showDetail(t)">Detail</button>
              <button v-if="t.source !== 'spp'" type="button" class="text-xs underline ml-2" @click="openEdit(t)">Edit</button>
              <button v-if="t.source !== 'spp'" type="button" class="text-xs underline ml-2 text-error" @click="remove(t)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
      <EmptyState v-if="!loading && !list.length" :icon="isIncome ? 'trending_up' : 'trending_down'" title="Belum ada transaksi" description="Tambahkan transaksi pertama untuk unit ini." />
      <div v-if="meta" class="flex items-center justify-between gap-2 p-3 border-t text-xs">
        <span class="text-on-surface-variant">Total {{ meta.total }} · Hal {{ meta.current_page }}/{{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">Prev</button>
          <button class="btn btn--ghost btn--sm" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">Next</button>
        </div>
      </div>
    </div>

    <div v-if="detail" class="fixed inset-0 z-50 bg-inverse-surface/50 flex items-center justify-center p-4" @click.self="detail = null">
      <div class="bg-white rounded-xl border border-outline-variant shadow-card w-full max-w-lg p-5 space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="font-headline-sm text-headline-sm font-semibold">Detail Transaksi</h2>
          <button type="button" class="btn btn--ghost btn--sm" @click="detail = null">Tutup</button>
        </div>
        <dl class="grid grid-cols-3 gap-2 text-sm">
          <dt class="text-on-surface-variant">Tanggal</dt><dd class="col-span-2">{{ formatDate(detail.transaction_date) }}</dd>
          <dt class="text-on-surface-variant">Jenis</dt><dd class="col-span-2 capitalize">{{ detail.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</dd>
          <dt class="text-on-surface-variant">Nominal</dt><dd class="col-span-2 font-bold">{{ formatRupiah(detail.amount) }}</dd>
          <dt class="text-on-surface-variant">Kategori</dt><dd class="col-span-2">{{ detail.category?.name || '—' }}</dd>
          <dt class="text-on-surface-variant">Keterangan</dt><dd class="col-span-2">{{ detail.description }}</dd>
          <dt v-if="detail.destination" class="text-on-surface-variant">Tujuan</dt><dd v-if="detail.destination" class="col-span-2">{{ detail.destination }}</dd>
          <dt class="text-on-surface-variant">Sumber</dt><dd class="col-span-2">{{ detail.source === 'spp' ? 'Pembayaran SPP' : 'Input manual' }}</dd>
          <dt class="text-on-surface-variant">Dicatat oleh</dt><dd class="col-span-2">{{ detail.creator?.name || '—' }}</dd>
        </dl>
        <a v-if="detail.proof_path" :href="`/storage/${detail.proof_path}`" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">Buka bukti transaksi</a>
      </div>
    </div>
  </FinanceShell>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FinanceShell from '../../Components/FinanceShell.vue'
import EmptyState from '../../Components/EmptyState.vue'
import CurrencyInput from '../../Components/CurrencyInput.vue'
import { formatDate, formatRupiah, normalizeAmount, useFinanceApi } from '../../composables/useFinance.js'
import { useDialog } from '../../composables/useDialog.js'

const route = useRoute()
const router = useRouter()
const dialog = useDialog()
const isIncome = computed(() => route.name === 'bendahara-pemasukan' || route.path.endsWith('/pemasukan'))
const type = computed(() => (isIncome.value ? 'income' : 'expense'))

const { loading, error, request } = useFinanceApi()
const list = ref([])
const meta = ref(null)
const page = ref(1)
const categories = ref([])
const categoryFilter = ref('')
const search = ref('')
const from = ref('')
const to = ref('')
const showForm = ref(false)
const saving = ref(false)
const detail = ref(null)
const editingId = ref(null)
const fileInput = ref(null)
let timer = null

const form = ref(emptyForm())

function emptyForm() {
  return { transaction_date: new Date().toISOString().slice(0, 10), amount: '', category_id: '', description: '', destination: '' }
}

function onSearch() {
  clearTimeout(timer)
  timer = setTimeout(() => goPage(1), 350)
}

function goPage(p) {
  page.value = Math.max(1, p)
  load()
}

async function load() {
  const q = new URLSearchParams({ type: type.value, page: String(page.value), per_page: '20' })
  if (search.value) q.set('search', search.value)
  if (categoryFilter.value) q.set('category_id', categoryFilter.value)
  if (from.value) q.set('from', from.value)
  if (to.value) q.set('to', to.value)
  try {
    const res = await request(`/api/finance/transactions?${q}`)
    list.value = res.data || []
    meta.value = { total: res.total, current_page: res.current_page, last_page: res.last_page }
  } catch { /* error sudah ditampilkan */ }
}

async function loadCategories() {
  try {
    categories.value = await request(`/api/finance/categories?type=${type.value}`)
  } catch { categories.value = [] }
}

function openCreate() {
  editingId.value = null
  form.value = emptyForm()
  showForm.value = true
}

function openEdit(t) {
  editingId.value = t.id
  form.value = {
    transaction_date: String(t.transaction_date).slice(0, 10),
    amount: normalizeAmount(t.amount),
    category_id: t.category_id || '',
    description: t.description,
    destination: t.destination || '',
  }
  showForm.value = true
}

function openPreview() {
  const q = {}
  if (from.value) q.from = from.value
  if (to.value) q.to = to.value
  if (categoryFilter.value) q.category_id = categoryFilter.value
  if (search.value) q.search = search.value
  router.push({ path: `/app/bendahara/preview/${isIncome.value ? 'pemasukan' : 'pengeluaran'}`, query: q })
}

function closeForm() {
  showForm.value = false
  editingId.value = null
}

async function submit() {
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('type', type.value)
    fd.append('transaction_date', form.value.transaction_date)
    fd.append('amount', form.value.amount)
    fd.append('description', form.value.description)
    if (form.value.category_id) fd.append('category_id', form.value.category_id)
    if (form.value.destination) fd.append('destination', form.value.destination)
    if (fileInput.value?.files?.[0]) fd.append('proof', fileInput.value.files[0])

    if (editingId.value) {
      fd.append('_method', 'PUT')
      await request(`/api/finance/transactions/${editingId.value}`, { method: 'POST', body: fd })
    } else {
      await request('/api/finance/transactions', { method: 'POST', body: fd })
    }
    closeForm()
    await load()
  } catch { /* error ditampilkan */ } finally {
    saving.value = false
  }
}

async function addCategory() {
  const name = await dialog.prompt('Nama kategori baru untuk ' + (isIncome.value ? 'pemasukan' : 'pengeluaran') + '.', {
    title: 'Tambah Kategori',
    placeholder: isIncome.value ? 'Mis. Infaq, Donasi' : 'Mis. Listrik, ATK',
    confirmText: 'Simpan',
  })
  if (!name || !name.trim()) return
  try {
    await request('/api/finance/categories', { method: 'POST', body: { type: type.value, name: name.trim() } })
    await loadCategories()
  } catch { /* error ditampilkan */ }
}

async function remove(t) {
  const ok = await dialog.confirm(`Hapus transaksi "${t.description}"? Tindakan ini tidak dapat dibatalkan.`, {
    title: 'Hapus Transaksi',
    variant: 'danger',
    confirmText: 'Hapus',
  })
  if (!ok) return
  try {
    await request(`/api/finance/transactions/${t.id}`, { method: 'DELETE' })
    await load()
  } catch { /* error ditampilkan */ }
}

function showDetail(t) {
  detail.value = t
}

watch(type, async () => {
  categoryFilter.value = ''
  search.value = ''
  page.value = 1
  await Promise.all([load(), loadCategories()])
})

onMounted(async () => {
  await Promise.all([load(), loadCategories()])
})
</script>
