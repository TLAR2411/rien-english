import { onMounted, onUnmounted, ref } from "vue"

// `define` values are only substituted during a production build — Vite's
// dev server leaves this identifier undeclared, so it must be read via
// `typeof` (never throws on an undeclared identifier) rather than referenced
// directly. In dev this resolves to null and the update check is skipped.
const CURRENT_VERSION = typeof __APP_BUILD_VERSION__ !== "undefined" ? String(__APP_BUILD_VERSION__) : null
const CHECK_INTERVAL = 10 * 60 * 1000 // 10 minutes
// Random per-client offset so many users don't poll the server in lockstep.
const CHECK_JITTER = Math.floor(Math.random() * 60 * 1000)
// Minimum gap between any two checks — mounts, tab switches and the interval
// all funnel through here, so this caps each client's request rate.
const MIN_CHECK_GAP = 2 * 60 * 1000 // 2 minutes

// Module-level so every component using this composable shares one state
// and only one polling loop runs regardless of how many navbars mount it.
const hasNewBuild = ref(false)
let watcherCount = 0
let intervalId = null
let lastCheckAt = 0
let isChecking = false

const checkForNewBuild = async () => {
  if (hasNewBuild.value || CURRENT_VERSION === null) return
  if (isChecking || Date.now() - lastCheckAt < MIN_CHECK_GAP) return

  isChecking = true
  lastCheckAt = Date.now()

  try {
    const response = await fetch(`/version.json?t=${Date.now()}`, {
      cache: "no-store",
    })

    if (!response.ok) return

    const data = await response.json()
    if (data?.version && String(data.version) !== CURRENT_VERSION) {
      hasNewBuild.value = true
    }
  } catch {
    // Offline or request blocked — try again on the next check.
  } finally {
    isChecking = false
  }
}

const handleVisibilityChange = () => {
  if (document.visibilityState === "visible") checkForNewBuild()
}

// Shared so both the navbar button and the blocking update dialog trigger
// the exact same reset — unregister SW, clear the PWA asset cache, then a
// cache-busting hard reload (new query string defeats the browser disk cache too).
export async function forceUpdate() {
  if ("serviceWorker" in navigator) {
    const registrations = await navigator.serviceWorker.getRegistrations()
    for (const registration of registrations) {
      await registration.unregister()
    }
  }

  if ("caches" in window) {
    const cacheNames = await caches.keys()
    await Promise.all(cacheNames.map((name) => caches.delete(name)))
  }

  sessionStorage.clear()

  // On iOS/WebKit, unregister() can resolve before the outgoing worker has
  // actually stopped controlling the page — navigating immediately risks
  // that worker still intercepting this very reload from its (now-cleared,
  // but not yet detached) cache. A short delay lets the detach finish first.
  await new Promise((resolve) => setTimeout(resolve, 300))

  const url = new URL(window.location.href)
  url.searchParams.set("reload", Date.now().toString())
  window.location.href = url.toString()
}

// Polling on an interval alone misses the common mobile case: a PWA
// resumed from the background after sitting idle for hours. Re-checking
// on visibilitychange catches that moment on both iOS and Android, unlike
// service-worker update detection which is unreliable there. (`focus` is
// deliberately not used — it fires on every alt-tab/devtools click.)
export function useBuildVersion() {
  onMounted(() => {
    watcherCount += 1

    if (!intervalId) {
      checkForNewBuild()
      intervalId = setInterval(checkForNewBuild, CHECK_INTERVAL + CHECK_JITTER)
      document.addEventListener("visibilitychange", handleVisibilityChange)
    }
  })

  onUnmounted(() => {
    watcherCount -= 1

    if (watcherCount <= 0) {
      clearInterval(intervalId)
      intervalId = null
      document.removeEventListener("visibilitychange", handleVisibilityChange)
    }
  })

  return { hasNewBuild, forceUpdate }
}
