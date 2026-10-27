import assert from 'node:assert/strict'
import test from 'node:test'
import { canAccessAdminModule, isAdminUser } from '../../resources/js/permissions.js'

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
