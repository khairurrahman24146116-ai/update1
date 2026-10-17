import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { CalculationMethod, Coordinates, PrayerTimes } from 'adhan'

const PRAYERS = [
  { key: 'fajr', label: 'Subuh' },
  { key: 'dhuhr', label: 'Dzuhur' },
  { key: 'asr', label: 'Ashar' },
  { key: 'maghrib', label: 'Maghrib' },
  { key: 'isha', label: 'Isya' },
]

function zonedParts(date, timeZone) {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date)
  const pick = (type) => Number(parts.find((p) => p.type === type)?.value)
  return { year: pick('year'), month: pick('month'), day: pick('day') }
}

function formatClock(date, timeZone) {
  return new Intl.DateTimeFormat('id-ID', {
    timeZone,
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(date)
}

function buildParameters() {
  const params = CalculationMethod.Other()
  params.fajrAngle = 20
  params.ishaAngle = 18
  return params
}

/**
 * Prayer-time countdown from site settings (client-side, no third-party API).
 *
 * @param {() => {latitude?: number|null, longitude?: number|null, timeZone?: string}|null} resolveConfig
 */
export function usePrayerTimes(resolveConfig) {
  const ready = ref(false)
  const nextKey = ref('fajr')
  const nextLabel = ref('Subuh')
  const countdown = ref('--:--:--')
  const timeZone = ref('Asia/Jakarta')
  const nextTime = ref(null)
  let params = null
  let timer = null

  const NextKey = computed(() => nextKey.value)
  const NextLabel = computed(() => nextLabel.value)
  const Countdown = computed(() => countdown.value)
  const NextClock = computed(() => (nextTime.value ? formatClock(nextTime.value, timeZone.value) : '--:--'))
  const Ready = computed(() => ready.value)

  function computeNext() {
    const config = resolveConfig?.()
    const lat = config ? Number(config.latitude) : NaN
    const lng = config ? Number(config.longitude) : NaN
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      ready.value = false
      countdown.value = '--:--:--'
      return
    }
    if (!params) params = buildParameters()

    timeZone.value = config.timeZone || 'Asia/Jakarta'
    const coordinates = new Coordinates(lat, lng)
    const now = new Date()
    const today = zonedParts(now, timeZone.value)
    const todays = new PrayerTimes(
      coordinates,
      new Date(today.year, today.month - 1, today.day),
      params
    )

    let target = null
    for (const prayer of PRAYERS) {
      const time = todays[prayer.key]
      if (time && time.getTime() > now.getTime()) {
        target = { ...prayer, time }
        break
      }
    }

    if (!target) {
      const tomorrow = new Date(today.year, today.month - 1, today.day + 1)
      const tomorrowTimes = new PrayerTimes(coordinates, tomorrow, params)
      target = { ...PRAYERS[0], time: tomorrowTimes.fajr }
    }

    nextKey.value = target.key
    nextLabel.value = target.label
    nextTime.value = target.time
    ready.value = true
  }

  function tick() {
    if (!ready.value) {
      computeNext()
      if (!ready.value) return
    }
    const diff = Math.max(0, nextTime.value.getTime() - Date.now())
    if (diff <= 0) {
      computeNext()
      return
    }
    const total = Math.floor(diff / 1000)
    const h = String(Math.floor(total / 3600)).padStart(2, '0')
    const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0')
    const s = String(total % 60).padStart(2, '0')
    countdown.value = `${h}:${m}:${s}`
  }

  function start() {
    stop()
    computeNext()
    tick()
    timer = setInterval(tick, 1000)
  }

  function stop() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  watch(
    () => {
      const config = resolveConfig?.()
      return config ? `${config.latitude}|${config.longitude}|${config.timeZone}` : null
    },
    () => {
      computeNext()
      tick()
    }
  )

  onBeforeUnmount(stop)

  return { start, stop, nextPrayerKey: NextKey, nextPrayerLabel: NextLabel, countdown: Countdown, nextClock: NextClock, ready: Ready }
}
