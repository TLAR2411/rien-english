<script setup>
import { useStudentLevelStore } from "@/stores/studentLevelStore";

/** Top bar (student system): "My level: B1". Choosing a level changes which lessons the student sees. */
const store = useStudentLevelStore();
const { locale } = useI18n({ useScope: "global" });

onMounted(() => store.loadLevels().catch(() => {}));

const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);
</script>

<template>
  <VMenu location="bottom end">
    <template #activator="{ props }">
      <VBtn
        v-bind="props"
        :variant="store.level ? 'text' : 'tonal'"
        color="primary"
        append-icon="tabler-chevron-down"
        class="text-none"
      >
        <template v-if="store.level">
          <span class="text-medium-emphasis me-1 d-none d-sm-inline">{{ $t("My level") }}:</span>
          {{ store.level.code }}
        </template>
        <template v-else>{{ $t("Choose your level") }}</template>
      </VBtn>
    </template>

    <VList density="comfortable" min-width="220">
      <VListItem
        v-for="level in store.levels"
        :key="level.id"
        :active="level.id === store.levelId"
        color="primary"
        @click="store.setLevel(level.id)"
      >
        <template #prepend>
          <VChip size="small" label class="me-3">{{ level.code }}</VChip>
        </template>
        <VListItemTitle>{{ levelName(level) }}</VListItemTitle>
      </VListItem>
    </VList>
  </VMenu>
</template>
