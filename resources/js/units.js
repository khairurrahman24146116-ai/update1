const DEFAULT_ENABLED_UNITS = ['lpi', 'sma']

export function enabledUnitCodes(user) {
  const codes = Array.isArray(user?.enabled_units) ? user.enabled_units : DEFAULT_ENABLED_UNITS

  return [...new Set(codes.map((code) => String(code).trim().toLowerCase()).filter(Boolean))]
}

export function isUnitEnabled(user, code) {
  return enabledUnitCodes(user).includes(String(code ?? '').trim().toLowerCase())
}
