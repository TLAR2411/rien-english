<script setup>
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import { api } from "@/utils/api";
import { useSettingStore } from "@/stores/settingStore";
import hasPermission from "@/utils/hasPermission";
import AdminStatTile from "@/views/admin/dashboards/AdminStatTile.vue";
import AdminBarList from "@/views/admin/dashboards/AdminBarList.vue";
import AdminPeopleList from "@/views/admin/dashboards/AdminPeopleList.vue";

const { t, locale } = useI18n();
const settingStore = useSettingStore();

const canView = hasPermission("view-users");
const canViewLog = hasPermission("view-activity-log");

const data = ref({});
const isLoading = ref(false);

const fetchData = async () => {
  if (!canView) return;
  try {
    isLoading.value = true;
    const res = await api.get("admin-dashboard");
    if (res.data.status) data.value = res.data.data;
  } catch (error) {
    console.error("Failed to fetch admin dashboard:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchData);
watch(() => settingStore.branch_id, fetchData);

const summary = computed(() => data.value.summary ?? {});
const isEn = computed(() => locale.value === "en");
const pick = (kh, en) => (isEn.value ? en || kh : kh || en);

// ── Relative time ("3 hours ago" / "៣ ម៉ោងមុន") ──
// Built by hand: many browsers have no Khmer data for Intl.RelativeTimeFormat
// and silently fall back to English.
// last_login is stored as Phnom Penh local time; activity times are UTC ISO.
const UNITS = [
  ["year", 31536000, "ឆ្នាំ"],
  ["month", 2592000, "ខែ"],
  ["day", 86400, "ថ្ងៃ"],
  ["hour", 3600, "ម៉ោង"],
  ["minute", 60, "នាទី"],
];
const khmerDigits = (n) =>
  String(n).replace(/\d/g, (d) => "០១២៣៤៥៦៧៨៩"[d]);

const ago = (value, tz) => {
  if (!value) return t("Never");
  const time = tz ? moment.tz(value, tz) : moment(value);
  const seconds = Math.max(0, moment().diff(time, "seconds"));
  const [unit, size, km] = UNITS.find(([, s]) => seconds >= s) ?? [];

  if (!unit) return isEn.value ? "just now" : "មុននេះបន្តិច";
  const n = Math.floor(seconds / size);
  return isEn.value
    ? `${n} ${unit}${n > 1 ? "s" : ""} ago`
    : `${khmerDigits(n)} ${km}មុន`;
};
const loginAgo = (value) => ago(value, "Asia/Phnom_Penh");

// ── Headline tiles ──
const share = (part, whole) => (whole ? part / whole : 0);
const percent = (part, whole) => Math.round(share(part, whole) * 100);

const tiles = computed(() => {
  const s = summary.value;
  const active = s.active_users ?? 0;
  const total = s.total_users ?? 0;
  const today = s.logged_in_today ?? 0;

  return [
    {
      title: "Active Users",
      value: active,
      icon: "tabler-users",
      color: "primary",
      caption: t("{n} of {total} accounts", { n: active, total }),
      progress: share(active, total),
      to: { name: "admin-users" },
    },
    {
      title: "Logged In Today",
      value: today,
      icon: "tabler-login-2",
      color: "success",
      caption: t("{pct}% of active users", { pct: percent(today, active) }),
      progress: share(today, active),
    },
    {
      title: "New This Month",
      value: s.new_this_month ?? 0,
      icon: "tabler-user-plus",
      color: "info",
      caption: moment().format("MM/YYYY"),
    },
    {
      title: "Active Branches",
      value: s.branches ?? 0,
      icon: "tabler-building",
      color: "secondary",
      caption: t("Sub accounts: {n}", { n: s.sub_accounts ?? 0 }),
      to: { name: "admin-branches" },
    },
  ];
});

// "Needs attention" tiles: status line with icon + label, never colour alone.
// The bar shows how big the problem is compared with all active users.
const alerts = computed(() => {
  const s = summary.value;
  const active = s.active_users ?? 0;
  const tile = (title, value, icon, to, whole = active) => ({
    title,
    value,
    icon,
    to,
    color: value ? "warning" : "success",
    status: value ? "warn" : "ok",
    progress: value ? share(value, whole) : null,
  });

  return [
    tile("Idle 30+ Days", s.idle_30_days ?? 0, "tabler-clock-pause", {
      name: "admin-users",
    }),
    tile("Never Logged In", s.never_logged_in ?? 0, "tabler-user-question", {
      name: "admin-users",
    }),
    tile(
      "Disabled Users",
      s.inactive_users ?? 0,
      "tabler-user-off",
      { name: "admin-users" },
      s.total_users ?? 0,
    ),
  ];
});

// ── Breakdowns ──
const byBranch = computed(() =>
  (data.value.by_branch ?? []).map((b) => ({
    key: b.id,
    label: pick(b.name_kh, b.name_en),
    sub: b.abbr,
    value: Number(b.total),
  })),
);

const byPosition = computed(() =>
  (data.value.by_position ?? []).map((p) => ({
    key: p.id,
    label: pick(p.name_kh, p.name_en),
    sub: p.abbr,
    value: Number(p.total),
  })),
);

const byRole = computed(() =>
  (data.value.by_role ?? []).map((r) => ({
    key: r.id,
    label: r.display_name,
    sub: r.abbr,
    value: Number(r.total),
  })),
);

// ── People lists ──
const person = (u, trailing, trailingColor) => ({
  key: u.id,
  name: u.name_kh,
  image: u.image_path,
  title: `${u.name_kh} (${u.name_en ?? ""})`,
  subtitle: [pick(u.position_name_kh, u.position_name_en), u.branch_abbr]
    .filter(Boolean)
    .join(" · "),
  trailing,
  trailingColor,
});

const recentLogins = computed(() =>
  (data.value.recent_logins ?? []).map((u) =>
    person(u, loginAgo(u.last_login)),
  ),
);

const idleUsers = computed(() =>
  (data.value.idle_users ?? []).map((u) =>
    person(u, loginAgo(u.last_login), u.last_login ? "warning" : "error"),
  ),
);

const recentActivity = computed(() =>
  (data.value.recent_activity ?? []).map((a) => ({
    key: a.id,
    name: a.causer?.name_kh ?? "System",
    image: a.causer?.image_path,
    title: a.causer?.name_kh ?? t("System"),
    subtitle: a.description,
    trailing: ago(a.created_at),
  })),
);
</script>

<template>
  <div v-if="!canView" class="text-center text-medium-emphasis pa-10">
    <VIcon icon="tabler-lock" size="36" class="mb-2" />
    <div>{{ t("You do not have permission to view this dashboard") }}</div>
  </div>

  <div v-else class="admin-dashboard">
    <!-- Headline numbers -->
    <div class="section-title">
      <VIcon icon="tabler-layout-dashboard" size="18" />
      {{ t("Overview") }}
    </div>
    <VRow class="match-height">
      <VCol v-for="tile in tiles" :key="tile.title" cols="12" sm="6" lg="3">
        <AdminStatTile v-bind="tile" :loading="isLoading" />
      </VCol>
    </VRow>

    <!-- Accounts that need an admin's attention -->
    <div class="section-title mt-6">
      <VIcon icon="tabler-alert-triangle" size="18" />
      {{ t("Needs Attention") }}
    </div>
    <VRow class="match-height">
      <VCol v-for="tile in alerts" :key="tile.title" cols="12" sm="6" lg="3">
        <AdminStatTile v-bind="tile" :loading="isLoading" />
      </VCol>
    </VRow>
    <div class="mb-2" />

    <!-- Where the staff are -->
    <VRow class="match-height">
      <VCol v-if="byBranch.length > 1" cols="12" md="6" lg="4">
        <AdminBarList
          title="Users by Branch"
          icon="tabler-building"
          :items="byBranch"
          :loading="isLoading"
        />
      </VCol>
      <VCol cols="12" md="6" :lg="byBranch.length > 1 ? 4 : 6">
        <AdminBarList
          title="Users by Position"
          icon="tabler-briefcase-2"
          :items="byPosition"
          :loading="isLoading"
        />
      </VCol>
      <VCol cols="12" md="6" :lg="byBranch.length > 1 ? 4 : 6">
        <AdminBarList
          title="Users by Role"
          icon="tabler-shield-lock"
          :items="byRole"
          :loading="isLoading"
        />
      </VCol>
    </VRow>

    <!-- Follow-up lists -->
    <VRow class="match-height">
      <VCol cols="12" md="6" lg="4">
        <AdminPeopleList
          title="Inactive Accounts"
          icon="tabler-clock-pause"
          color="warning"
          :items="idleUsers"
          empty-text="Everyone has logged in recently"
          :to="{ name: 'admin-users' }"
          :loading="isLoading"
        />
      </VCol>
      <VCol cols="12" md="6" lg="4">
        <AdminPeopleList
          title="Recent Logins"
          icon="tabler-login-2"
          color="success"
          :items="recentLogins"
          :loading="isLoading"
        />
      </VCol>
    </VRow>

    <VRow v-if="canViewLog">
      <VCol cols="12">
        <AdminPeopleList
          title="Recent Activity"
          icon="tabler-activity"
          :items="recentActivity"
          :to="{ name: 'admin-activity-log' }"
          :loading="isLoading"
        />
      </VCol>
    </VRow>

  </div>
</template>

<style lang="scss" scoped>
// Same Khmer font as the Team Structure cards. Vuetify's typography classes
// set their own font-family, so every element inside needs the override.
.admin-dashboard,
.admin-dashboard :deep(*) {
  font-family: inter, kantumruy, sans-serif !important;
}

.section-title {
  display: flex;
  align-items: center;
  color: rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity));
  font-size: 0.95rem;
  font-weight: 600;
  gap: 8px;
  margin-block-end: 12px;
}
</style>
