import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { canAccessAdminModule, hasFinanceAccess, isAdminUser } from '../../resources/js/permissions.js'

const publicLayouts = [
  readFileSync(new URL('../../resources/js/Components/LpiLayout.vue', import.meta.url), 'utf8'),
  readFileSync(new URL('../../resources/js/Components/PublicLayout.vue', import.meta.url), 'utf8'),
]

test('public layouts never link to the secret login path', () => {
  for (const layout of publicLayouts) {
    assert.equal(layout.includes('LOGIN_PATH'), false)
    assert.equal(layout.includes('akses-dalam-9f2k'), false)
    assert.equal(layout.includes('Masuk'), false)
  }
})

const securityMenu = { permission: 'logs.view', adminOnly: true }

test('admin_utama and legacy admin see schedule and payment admin actions', () => {
  assert.equal(isAdminUser({ role: 'admin_utama' }), true)
  assert.equal(isAdminUser({ role: 'admin' }), true)
  assert.equal(isAdminUser({ role: 'operator' }), false)
})

test('admin_utama sees the security menu only with logs.view', () => {
  assert.equal(canAccessAdminModule({ role: 'admin_utama', permissions: ['logs.view'] }, securityMenu), true)
  assert.equal(canAccessAdminModule({ role: 'admin_utama', permissions: [] }, securityMenu), false)
})

test('operator never sees the admin security menu', () => {
  assert.equal(canAccessAdminModule({ role: 'operator', permissions: ['logs.view'] }, securityMenu), false)
})

test('guru with finance permission sees the finance menu', () => {
  assert.equal(hasFinanceAccess({ role: 'guru', permissions: ['finance.view'] }), true)
})

test('guru with bendahara unit jabatan sees the finance menu without role change', () => {
  assert.equal(hasFinanceAccess({
    role: 'guru',
    permissions: [],
    units: [{ code: 'SMA', jabatan: 'Bendahara SMA' }],
  }), true)
})

test('guru without finance permission or jabatan does not see the finance menu', () => {
  assert.equal(hasFinanceAccess({
    role: 'guru',
    permissions: [],
    units: [{ code: 'SMA', jabatan: 'Guru Mata Pelajaran' }],
  }), false)
})

test('server finance access flag is honoured', () => {
  assert.equal(hasFinanceAccess({ role: 'guru', permissions: [], can_access_finance: true }), true)
})

test('legacy admin finance route redirects to the active finance area', () => {
  const router = readFileSync(new URL('../../resources/js/router.js', import.meta.url), 'utf8')

  assert.equal(router.includes("path: '/app/admin/finance', redirect: '/app/bendahara'"), true)
  assert.equal(router.includes('Pages/Admin/Finance.vue'), false)
})

test('welfare permit actions use the shared dialog instead of window.prompt', () => {
  const page = readFileSync(new URL('../../resources/js/Pages/Guru/Akhlak.vue', import.meta.url), 'utf8')

  assert.equal(page.includes('window.prompt'), false)
  assert.equal(page.includes('useDialog'), true)
  assert.equal((page.match(/dialog\.prompt\(/g) || []).length, 4)
})
