import { ref } from 'vue'

/**
 * Memuat data struktur organisasi publik.
 * Data disajikan sebagai daftar datar berisi referensi atasan (parent_id);
 * pembentukan hierarki dilakukan di komponen bagan.
 */
export function useOrganization() {
  const members = ref([])
  const loading = ref(true)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const res = await fetch('/api/organization-members/public', {
        headers: { Accept: 'application/json' },
      })
      if (!res.ok) throw new Error('Gagal memuat struktur organisasi.')
      const json = await res.json()
      members.value = Array.isArray(json) ? json : (json.members || [])
    } catch (e) {
      error.value = e.message || 'Gagal memuat struktur organisasi.'
    } finally {
      loading.value = false
    }
  }

  return { members, loading, error, load }
}
