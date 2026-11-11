import { computed, ref } from "vue";

// `beforeinstallprompt` can fire before any Vue component mounts, so it's
// captured at module scope (once, shared by every caller) rather than inside
// the composable — the same pattern as useBuildVersion.js.
const deferredPrompt = ref(null);

const isStandaloneDisplay = () =>
  window.matchMedia?.("(display-mode: standalone)")?.matches ||
  window.navigator.standalone === true; // legacy iOS Safari flag

const isInstalled = ref(
  typeof window !== "undefined" && isStandaloneDisplay(),
);

if (typeof window !== "undefined") {
  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault(); // stop the browser's own mini-infobar; we show our own button
    deferredPrompt.value = event;
  });

  window.addEventListener("appinstalled", () => {
    isInstalled.value = true;
    deferredPrompt.value = null;
  });
}

// No iOS browser ever fires `beforeinstallprompt` — Apple has never
// implemented it in WebKit, and every iOS browser (Chrome, Firefox, Edge
// included) is a WebKit skin under the hood. "Add to Home Screen" instead
// lives in the OS-level Share sheet, which every iOS browser's own Share
// button opens — so this only needs to detect the platform, not the specific
// browser. (iPadOS masquerades as desktop Safari's UA by default since
// iPadOS 13, which this can't see through — no `beforeinstallprompt` fires
// there either, so those users just won't get an install hint at all.)
const isIos = () => {
  if (typeof navigator === "undefined") return false;
  return /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
};

export function usePwaInstall() {
  // A real install prompt is available (Chrome/Edge on Android & desktop).
  const canInstall = computed(
    () => !isInstalled.value && !!deferredPrompt.value,
  );

  // No native prompt exists, but the platform supports install via manual steps.
  const needsIosInstructions = computed(
    () => !isInstalled.value && !deferredPrompt.value && isIos(),
  );

  const promptInstall = async () => {
    if (!deferredPrompt.value) return;

    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === "accepted") isInstalled.value = true;

    // A prompt event can only be used once.
    deferredPrompt.value = null;
  };

  return { canInstall, needsIosInstructions, isInstalled, promptInstall };
}
