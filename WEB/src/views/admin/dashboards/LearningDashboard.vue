<script setup>
import { api } from "@/utils/api";
import { auth } from "@/utils/auth";
import AppStatTile from "@/components/AppStatTile.vue";

// One tile per number from GET admin/dashboard. Add a tile here when the API adds a number.
const TILES = [
  { key: "students", title: "Students", icon: "tabler-school" },
  { key: "teachers", title: "Teachers", icon: "tabler-chalkboard" },
];

const counts = ref({});
const isLoading = ref(true);

onMounted(async () => {
  try {
    const res = await api.get("admin/dashboard");
    counts.value = res.data.data ?? {};
  } catch (error) {
    console.error("Failed to load the dashboard:", error);
  } finally {
    isLoading.value = false;
  }
});

const userName = computed(() => auth()?.user?.name_en || auth()?.user?.name_kh || "");
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("Dashboard") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ $t("Welcome back") }}<template v-if="userName">, {{ userName }}</template>
      </p>
    </header>

    <VRow>
      <VCol v-for="tile in TILES" :key="tile.key" cols="6" md="3">
        <AppStatTile
          :title="tile.title"
          :icon="tile.icon"
          :value="counts[tile.key] ?? 0"
          :loading="isLoading"
          dense
        />
      </VCol>
    </VRow>
  </section>
</template>
