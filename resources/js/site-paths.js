export const LOGIN_PATH = '/akses-dalam-9f2k'
export const SMA_BASE = '/sma'
export const smaPath = (p = '') => `${SMA_BASE}${p.startsWith('/') ? p : `/${p}`}`
