export const ADMIN_MANAGE_PERMISSIONS = [
  'users.view', 'users.create', 'users.update', 'users.delete',
  'permissions.view', 'permissions.manage',
  'teachers.create', 'teachers.update', 'teachers.delete',
  'students.create', 'students.update', 'students.delete', 'students.import', 'students.export',
  'classrooms.create', 'classrooms.update', 'classrooms.delete',
  'subjects.create', 'subjects.update', 'subjects.delete',
  'schedules.create', 'schedules.update', 'schedules.delete',
  'attendance.create', 'attendance.import',
  'scores.update',
  'ppdb.waves.view', 'ppdb.waves.create', 'ppdb.waves.update', 'ppdb.waves.delete',
  'content.news.view', 'content.news.create', 'content.news.update', 'content.news.delete', 'content.news.publish',
  'content.gallery.view', 'content.gallery.create', 'content.gallery.update', 'content.gallery.delete',
  'content.structure.view', 'content.structure.create', 'content.structure.update', 'content.structure.delete',
  'content.principal.view',
  'settings.site.manage', 'settings.homepage.manage', 'settings.landing.manage',
  'system.backup.create', 'system.backup.download',
]

export const FINANCE_PERMISSIONS = [
  'finance.view', 'finance.transactions.view',
  'spp.view', 'reports.finance.view',
]

export const FINANCE_ROLES = ['bendahara', 'bendahara_lpi', 'bendahara_sma']

export function permissionsOf(user) {
  return Array.isArray(user?.permissions) ? user.permissions : []
}

export function hasPermissionData(user) {
  return Array.isArray(user?.permissions)
}

export function can(user, permission) {
  return permissionsOf(user).includes(permission)
}

export function canAny(user, permissions = []) {
  const list = permissionsOf(user)
  return permissions.some((p) => list.includes(p))
}

export function isAdminUser(user) {
  return user?.role === 'admin'
}

export function hasAdminAreaAccess(user) {
  if (isAdminUser(user)) return true
  return canAny(user, ADMIN_MANAGE_PERMISSIONS)
}

export function hasFinanceAccess(user) {
  if (isAdminUser(user)) return true
  if (FINANCE_ROLES.includes(user?.role)) return true
  return canAny(user, FINANCE_PERMISSIONS)
}
