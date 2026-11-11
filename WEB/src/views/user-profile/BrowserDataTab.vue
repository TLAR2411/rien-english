<script setup>
import { useDialog } from "@/composables/useDialog";
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const { showDialog } = useDialog();

// Never touched, so the user stays logged in on the same branch
const PROTECTED_KEYS = ["accessToken", "refreshToken", "token", "app", "setting"];
const TABLE_PREFIX = "table-state-";

const options = [
  {
    value: "cache",
    title: "App cache & offline files",
    desc: "Downloaded app files. Clear this if the app looks old or broken.",
    icon: "tabler-database",
    color: "primary",
  },
  {
    value: "session",
    title: "Session data",
    desc: "Temporary data for this tab, such as recent lists.",
    icon: "tabler-clock",
    color: "info",
  },
  {
    value: "tables",
    title: "Saved table settings",
    desc: "Filters, sorting and page size remembered on each list.",
    icon: "tabler-table",
    color: "warning",
  },
  {
    value: "preferences",
    title: "Other saved preferences",
    desc: "Theme and form defaults saved in this browser.",
    icon: "tabler-adjustments",
    color: "error",
  },
];

const selected = ref(["cache", "session", "tables"]);
const isClearing = ref(false);
const usage = ref(null);
const counts = ref({ cache: 0, session: 0, tables: 0, preferences: 0 });

const safeKeys = (storage) => {
  try {
    return Object.keys(storage);
  } catch {
    return [];
  }
};

const preferenceKeys = () =>
  safeKeys(localStorage).filter(
    (k) => !PROTECTED_KEYS.includes(k) && !k.startsWith(TABLE_PREFIX),
  );

const formatBytes = (bytes) => {
  if (!bytes) return "0 KB";
  const units = ["B", "KB", "MB", "GB"];
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), 3);
  return `${(bytes / 1024 ** i).toFixed(i ? 1 : 0)} ${units[i]}`;
};

const loadInfo = async () => {
  let cacheCount = 0;
  if ("caches" in window) {
    try {
      cacheCount = (await caches.keys()).length;
    } catch {}
  }
  counts.value = {
    cache: cacheCount,
    session: safeKeys(sessionStorage).length,
    tables: safeKeys(localStorage).filter((k) => k.startsWith(TABLE_PREFIX))
      .length,
    preferences: preferenceKeys().length,
  };

  if (navigator.storage?.estimate) {
    try {
      const { usage: used, quota } = await navigator.storage.estimate();
      usage.value = { used, quota };
    } catch {}
  }
};

onMounted(loadInfo);

const usagePercent = computed(() =>
  usage.value?.quota
    ? Math.min(100, (usage.value.used / usage.value.quota) * 100)
    : 0,
);

// A page can't clear the whole browser, so show the steps for the
// browser + device the user is on. Settings links (chrome:// etc.) can't be
// opened from a page, only copied and pasted into the address bar.
const detectBrowser = () => {
  const ua = navigator.userAgent || "";
  const isIOS =
    /iPhone|iPad|iPod/.test(ua) ||
    (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1); // iPadOS
  const isAndroid = /Android/.test(ua);
  const isMac = /Macintosh/.test(ua);

  if (isIOS) {
    if (/CriOS/.test(ua)) return { id: "ios-chrome", isMac };
    return { id: "ios-safari", isMac };
  }
  if (isAndroid) {
    if (/SamsungBrowser/.test(ua)) return { id: "samsung", isMac };
    return { id: "android-chrome", isMac };
  }
  if (/Edg\//.test(ua)) return { id: "edge", isMac };
  if (/Firefox\//.test(ua)) return { id: "firefox", isMac };
  if (/Chrome\//.test(ua)) return { id: "chrome", isMac };
  if (/Safari\//.test(ua) && isMac) return { id: "mac-safari", isMac };
  return { id: "other", isMac };
};

const browser = detectBrowser();
const mod = browser.isMac ? "⌘" : "Ctrl";
const del = browser.isMac ? "⌫" : "Delete";

const GUIDES = {
  chrome: {
    name: "Chrome",
    icon: "tabler-brand-chrome",
    keys: [mod, "Shift", del],
    link: "chrome://settings/clearBrowserData",
  },
  edge: {
    name: "Edge",
    icon: "tabler-brand-edge",
    keys: [mod, "Shift", del],
    link: "edge://settings/clearBrowserData",
  },
  firefox: {
    name: "Firefox",
    icon: "tabler-brand-firefox",
    keys: [mod, "Shift", del],
    link: "about:preferences#privacy",
  },
  "mac-safari": {
    name: "Safari (Mac)",
    icon: "tabler-brand-safari",
    steps: [
      "In the menu bar, click History",
      "Click Clear History",
      "Choose all history, then click Clear History",
    ],
  },
  "ios-safari": {
    name: "Safari (iPhone / iPad)",
    icon: "tabler-brand-apple",
    steps: [
      "Open the Settings app",
      "Tap Apps, then Safari (older iOS: tap Safari)",
      "Tap Clear History and Website Data",
    ],
  },
  "ios-chrome": {
    name: "Chrome (iPhone / iPad)",
    icon: "tabler-brand-chrome",
    steps: [
      "Tap the ⋯ menu at the bottom",
      "Tap Delete Browsing Data",
      "Choose All time, then tap Delete Browsing Data",
    ],
  },
  "android-chrome": {
    name: "Chrome (Android)",
    icon: "tabler-brand-android",
    steps: [
      "Tap the ⋮ menu at the top right",
      "Tap Delete browsing data",
      "Choose All time, then tap Delete data",
    ],
    link: "chrome://settings/clearBrowserData",
  },
  samsung: {
    name: "Samsung Internet",
    icon: "tabler-brand-samsungpass",
    steps: [
      "Tap the ☰ menu at the bottom right",
      "Tap Settings, then Personal browsing data",
      "Tap Delete browsing data, then Delete",
    ],
  },
  other: {
    name: "",
    icon: "tabler-world",
    steps: [
      "Open your browser settings",
      "Find Privacy or History",
      "Choose Clear browsing data",
    ],
  },
};

const guide = GUIDES[browser.id];
const copied = ref(false);

const copyLink = async () => {
  if (!guide.link) return;
  try {
    await navigator.clipboard.writeText(guide.link);
  } catch {
    // Older browsers / non-HTTPS: fall back to a hidden textarea
    const el = document.createElement("textarea");
    el.value = guide.link;
    document.body.appendChild(el);
    el.select();
    document.execCommand("copy");
    el.remove();
  }
  copied.value = true;
  setTimeout(() => (copied.value = false), 2000);
};

const clearData = async () => {
  if (!selected.value.length || isClearing.value) return;

  const confirmed = await showDialog({
    title: t("Clear the selected browser data? The page will reload."),
    icon: "warning",
    confirmColor: "error",
    confirmText: "Confirm",
  });
  if (!confirmed) return;

  isClearing.value = true;
  try {
    if (selected.value.includes("tables")) {
      safeKeys(localStorage)
        .filter((k) => k.startsWith(TABLE_PREFIX))
        .forEach((k) => localStorage.removeItem(k));
    }
    if (selected.value.includes("preferences")) {
      preferenceKeys().forEach((k) => localStorage.removeItem(k));
    }
    if (selected.value.includes("session")) {
      sessionStorage.clear();
    }
    if (selected.value.includes("cache")) {
      if ("serviceWorker" in navigator) {
        const regs = await navigator.serviceWorker.getRegistrations();
        await Promise.all(regs.map((r) => r.unregister()));
      }
      if ("caches" in window) {
        const names = await caches.keys();
        await Promise.all(names.map((n) => caches.delete(n)));
      }
      // Let the service worker detach before reloading
      await new Promise((resolve) => setTimeout(resolve, 300));
    }
  } catch (error) {
    console.error("Failed to clear browser data:", error);
  }

  // Hard reload; the query string skips the browser disk cache
  const url = new URL(window.location.href);
  url.searchParams.set("reload", Date.now().toString());
  window.location.href = url.toString();
};
</script>

<template>
  <VCardText>
    <VRow>
      <!-- Storage used -->
      <VCol cols="12">
        <div class="storage-box">
          <VAvatar color="primary" variant="tonal" rounded size="44">
            <VIcon icon="tabler-device-desktop" size="24" />
          </VAvatar>
          <div class="flex-grow-1">
            <div class="d-flex justify-space-between mb-1">
              <span class="font-weight-medium">
                {{ $t("Storage used by this app") }}
              </span>
              <span v-if="usage" class="text-medium-emphasis">
                {{ formatBytes(usage.used) }} / {{ formatBytes(usage.quota) }}
              </span>
            </div>
            <VProgressLinear
              :model-value="usagePercent"
              color="primary"
              rounded
              height="8"
            />
            <div class="text-caption text-medium-emphasis mt-2">
              <VIcon icon="tabler-shield-check" size="14" color="success" />
              {{ $t("You will stay logged in. Your login and branch are kept.") }}
            </div>
          </div>
        </div>
      </VCol>

      <!-- Full browser clear: a page can't do this, so guide the user -->
      <VCol cols="12">
        <div class="chrome-box">
          <VAvatar color="secondary" variant="tonal" rounded size="44">
            <VIcon :icon="guide.icon" size="24" />
          </VAvatar>
          <div class="flex-grow-1">
            <div class="font-weight-medium">
              {{ $t("Clear all browser history") }}
              <VChip v-if="guide.name" size="x-small" label class="ms-1">
                {{ guide.name }}
              </VChip>
            </div>

            <!-- Desktop: shortcut -->
            <div v-if="guide.keys" class="text-body-2 text-medium-emphasis">
              {{ $t("Press") }}
              <template v-for="(key, i) in guide.keys" :key="key">
                <kbd>{{ key }}</kbd>
                <span v-if="i < guide.keys.length - 1"> + </span>
              </template>
            </div>

            <!-- Mobile / Safari: steps -->
            <ol v-if="guide.steps" class="guide-steps text-body-2">
              <li v-for="step in guide.steps" :key="step">{{ $t(step) }}</li>
            </ol>

            <div v-if="guide.link" class="text-body-2 text-medium-emphasis">
              {{ $t("or paste this link in the address bar:") }}
              <code>{{ guide.link }}</code>
            </div>
          </div>
          <VBtn
            v-if="guide.link"
            variant="tonal"
            color="secondary"
            @click="copyLink"
          >
            <VIcon start :icon="copied ? 'tabler-check' : 'tabler-copy'" />
            {{ copied ? $t("Copied") : $t("Copy link") }}
          </VBtn>
        </div>
      </VCol>

      <!-- Choices -->
      <VCol v-for="item in options" :key="item.value" cols="12" md="6">
        <label
          class="data-option"
          :class="{ 'is-active': selected.includes(item.value) }"
        >
          <VAvatar :color="item.color" variant="tonal" rounded size="40">
            <VIcon :icon="item.icon" size="22" />
          </VAvatar>
          <div class="flex-grow-1">
            <div class="d-flex align-center gap-2">
              <span class="font-weight-medium">{{ $t(item.title) }}</span>
              <VChip size="x-small" variant="tonal" label>
                {{ counts[item.value] }}
              </VChip>
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ $t(item.desc) }}
            </div>
          </div>
          <VCheckbox
            v-model="selected"
            :value="item.value"
            hide-details
            density="compact"
          />
        </label>
      </VCol>

      <VCol cols="12" class="d-flex justify-end gap-3">
        <VBtn variant="tonal" color="secondary" @click="loadInfo">
          <VIcon start icon="tabler-refresh" />
          {{ $t("Refresh") }}
        </VBtn>
        <VBtn
          color="error"
          :loading="isClearing"
          :disabled="!selected.length"
          @click="clearData"
        >
          <VIcon start icon="tabler-trash" />
          {{ $t("Clear browser data") }}
        </VBtn>
      </VCol>
    </VRow>
  </VCardText>
</template>

<style scoped lang="scss">
.storage-box {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.025);
}

.chrome-box {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;

  kbd {
    padding: 1px 6px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 4px;
    font-size: 0.75rem;
    background: rgba(var(--v-theme-on-surface), 0.04);
  }

  code {
    font-size: 0.75rem;
    word-break: break-all;
  }

  .guide-steps {
    margin: 4px 0;
    padding-inline-start: 20px;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));

    li {
      padding: 1px 0;
    }
  }
}

.data-option {
  display: flex;
  align-items: center;
  gap: 14px;
  height: 100%;
  padding: 14px 16px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;

  &.is-active {
    border-color: rgb(var(--v-theme-primary));
    background: rgba(var(--v-theme-primary), 0.04);
  }
}
</style>
