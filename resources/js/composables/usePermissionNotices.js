import { ref, watch } from 'vue'

const SNAPSHOT_PREFIX = 'madani_permission_snapshot_'
const NOTICE_PREFIX = 'madani_permission_notices_'

function read(key, fallback) {
  try {
    const raw = localStorage.getItem(key)
    return raw ? JSON.parse(raw) : fallback
  } catch {
    return fallback
  }
}

function write(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    return
  }
}

function sorted(list) {
  return Array.isArray(list) ? [...list].sort() : []
}

export function usePermissionNotices(user) {
  const notices = ref([])

  function snapshotKey(id) {
    return `${SNAPSHOT_PREFIX}${id}`
  }

  function noticesKey(id) {
    return `${NOTICE_PREFIX}${id}`
  }

  function loadNotices(id) {
    notices.value = read(noticesKey(id), [])
  }

  function saveNotices(id) {
    write(noticesKey(id), notices.value)
  }

  function sync(currentUser) {
    if (!currentUser?.id || currentUser.role === 'admin') {
      notices.value = []
      return
    }

    const id = currentUser.id
    const currentPermissions = sorted(currentUser.permissions)
    const labels = currentUser.permission_labels || {}
    const previous = read(snapshotKey(id), null)

    if (previous === null) {
      write(snapshotKey(id), { permissions: currentPermissions, labels })
      loadNotices(id)
      return
    }

    const previousPermissions = sorted(previous.permissions)
    const added = currentPermissions.filter((permission) => !previousPermissions.includes(permission))
    const removed = previousPermissions.filter((permission) => !currentPermissions.includes(permission))

    write(snapshotKey(id), { permissions: currentPermissions, labels })

    if (added.length === 0 && removed.length === 0) {
      loadNotices(id)
      return
    }

    const created = Date.now()
    const fresh = [
      ...added.map((permission, index) => ({
        id: `${id}:grant:${permission}:${created}:${index}`,
        type: 'grant',
        permission,
        label: labels[permission] || previous.labels?.[permission] || permission,
        created_at: created,
      })),
      ...removed.map((permission, index) => ({
        id: `${id}:revoke:${permission}:${created}:${index}`,
        type: 'revoke',
        permission,
        label: labels[permission] || previous.labels?.[permission] || permission,
        created_at: created,
      })),
    ]

    notices.value = [...read(noticesKey(id), []), ...fresh].slice(-20)
    saveNotices(id)
  }

  function dismiss(notice) {
    notices.value = notices.value.filter((item) => item.id !== notice.id)
    if (user.value?.id) {
      saveNotices(user.value.id)
    }
  }

  watch(
    user,
    (currentUser) => {
      if (!currentUser?.id) {
        notices.value = []
        return
      }

      loadNotices(currentUser.id)
      sync(currentUser)
    },
    { immediate: true },
  )

  return { notices, dismiss }
}
