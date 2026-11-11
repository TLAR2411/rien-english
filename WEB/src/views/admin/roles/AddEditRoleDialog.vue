<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { debounce } from "lodash";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";

const props = defineProps({
  roleData: { type: Object, default: () => ({}) },
  rolePermissions: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
  isDialogVisible: { type: Boolean, required: true },
  loading: { type: Boolean, default: undefined },
});

const emit = defineEmits(["onCreate", "onUpdate", "update:isDialogVisible"]);

const roleDataCopy = ref({ ...props.roleData });
const selected = ref([]); // flat array of selected permission IDs
const search = ref("");
const openPanels = ref([]);

watch(
  () => props.roleData,
  (val) => {
    roleDataCopy.value = { ...val };
  },
  { deep: true },
);
watch(
  () => props.rolePermissions,
  (val) => {
    selected.value = val.map((p) => p.id);
  },
  { immediate: true, deep: true },
);

// Filter + group permissions reactively
const groupedPermissions = computed(() => {
  const q = search.value.toLowerCase();

  return props.permissions.reduce((acc, item) => {
    const g = item.group || "Other";

    // Include if: no search, OR group name matches, OR permission name matches
    if (
      !q ||
      g.toLowerCase().includes(q) ||
      item.display_name.toLowerCase().includes(q)
    ) {
      (acc[g] ??= []).push(item);
    }

    return acc;
  }, {});
});

// Open all panels when dialog opens or search changes
watch(
  [() => props.isDialogVisible, groupedPermissions],
  async ([visible, groups]) => {
    if (visible && Object.keys(groups).length) {
      await nextTick();
      openPanels.value = Object.keys(groups);
    } else if (!visible) {
      openPanels.value = [];
    }
  },
  { immediate: true, flush: "post" },
);

// Per-group computed helpers
const groupSelectedCount = (group) =>
  (groupedPermissions.value[group] ?? []).filter((p) =>
    selected.value.includes(p.id),
  ).length;

const isGroupAll = (group) =>
  groupSelectedCount(group) === (groupedPermissions.value[group]?.length ?? 0);
const isGroupSome = (group) =>
  groupSelectedCount(group) > 0 && !isGroupAll(group);

const toggleGroup = (group) => {
  const ids = (groupedPermissions.value[group] ?? []).map((p) => p.id);
  if (isGroupAll(group)) {
    selected.value = selected.value.filter((id) => !ids.includes(id));
  } else {
    selected.value = [...new Set([...selected.value, ...ids])];
  }
};

const selectAllGlobal = () => {
  selected.value = props.permissions.map((p) => p.id);
};
const unselectAllGlobal = () => {
  selected.value = [];
};

const totalSelected = computed(() => selected.value.length);
const totalPermissions = computed(() => props.permissions.length);

const resetData = () => {
  roleDataCopy.value = { name: "", display_name: "", abbr: "" };
  selected.value = [];
  search.value = "";
};

const onFormSubmit = debounce(async (refForm) => {
  if (!refForm) return;
  const { valid } = await refForm;
  if (!valid) return;
  const payload = {
    roleData: roleDataCopy.value,
    permissions: selected.value,
  };
  const event = roleDataCopy.value.id ? "onUpdate" : "onCreate";
  emit(event, payload, (res) => {
    if (res === 200) resetData();
  });
}, 500);

const onCloseDialog = () => {
  resetData();
  emit("update:isDialogVisible", false);
};

const formatName = (str) =>
  str
    .split("-")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
</script>

<template>
  <AppDialog
    :title="roleDataCopy.id ? 'Update Role' : 'Create Role'"
    :is-dialog-visible="isDialogVisible"
    :loading="loading"
    @update:is-dialog-visible="
      (value) => $emit('update:isDialogVisible', value)
    "
    pin-footer
    is-footer
    is-submit
    @onSubmit="onFormSubmit"
  >
    <VRow class="mb-1">
      <VCol cols="12" sm="4">
        <AppTextField
          v-model="roleDataCopy.name"
          placeholder="user"
          :rules="[requiredValidator]"
        >
          <template #label>{{ $t("Name") }}</template>
        </AppTextField>
      </VCol>
      <VCol cols="12" sm="4">
        <AppTextField
          v-model="roleDataCopy.display_name"
          placeholder="អ្នកប្រើប្រាស់"
          :rules="[requiredValidator]"
        >
          <template #label>{{ $t("Display Name") }}</template>
        </AppTextField>
      </VCol>
      <VCol cols="12" sm="4">
        <AppTextField
          class="abbr-field"
          v-model="roleDataCopy.abbr"
          placeholder="USR"
          :rules="[requiredValidator]"
        >
          <template #label>{{ $t("Abbr") }}</template>
        </AppTextField>
      </VCol>
    </VRow>

    <VDivider class="mb-4" />

    <!-- ── Permissions header ── -->
    <div class="d-flex align-center justify-space-between mb-3">
      <div class="d-flex align-center gap-2">
        <span class="text-subtitle-2 font-weight-bold">Permissions</span>
        <VChip size="small" color="primary" variant="tonal">
          {{ totalSelected }} / {{ totalPermissions }}
        </VChip>
      </div>
      <div class="d-flex gap-2">
        <VBtn
          size="x-small"
          variant="tonal"
          color="success"
          @click="selectAllGlobal"
        >
          Select All
        </VBtn>
        <VBtn
          size="x-small"
          variant="tonal"
          color="error"
          @click="unselectAllGlobal"
        >
          Clear All
        </VBtn>
      </div>
    </div>

    <!-- ── Search ── -->
    <VTextField
      v-model="search"
      placeholder="Search permissions..."
      prepend-inner-icon="tabler-search"
      clearable
      clear-icon="tabler-x"
      density="compact"
      variant="outlined"
      class="mb-3"
      hide-details
    />

    <!-- ── Permission groups ── -->
    <VExpansionPanels v-model="openPanels" multiple>
      <VExpansionPanel
        v-for="(groupPermissions, group) in groupedPermissions"
        :key="group"
        :value="group"
        elevation="0"
        class="border mb-2 rounded"
      >
        <VExpansionPanelTitle class="py-2">
          <div class="d-flex align-center gap-2 w-100">
            <!-- Master checkbox for this group -->
            <VCheckbox
              :model-value="isGroupAll(group)"
              :indeterminate="isGroupSome(group)"
              density="compact"
              hide-details
              color="primary"
              @click.stop="toggleGroup(group)"
            />
            <span class="text-body-2 font-weight-medium">{{
              formatName(group)
            }}</span>
            <VSpacer />
            <VChip
              size="x-small"
              :color="
                isGroupAll(group)
                  ? 'success'
                  : isGroupSome(group)
                    ? 'warning'
                    : 'default'
              "
              variant="tonal"
              class="mr-2"
            >
              {{ groupSelectedCount(group) }} / {{ groupPermissions.length }}
            </VChip>
          </div>
        </VExpansionPanelTitle>

        <VExpansionPanelText class="pt-1 pb-3">
          <VDivider class="mb-3" />
          <div
            style="
              display: grid;
              grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
              gap: 0 8px;
            "
          >
            <VCheckbox
              v-for="item in groupPermissions"
              :key="item.id"
              v-model="selected"
              :value="item.id"
              :label="formatName(item.display_name)"
              density="compact"
              hide-details
              color="primary"
            />
          </div>
        </VExpansionPanelText>
      </VExpansionPanel>
    </VExpansionPanels>

    <!-- Empty state -->
    <div
      v-if="search && !Object.keys(groupedPermissions).length"
      class="text-center py-8 text-medium-emphasis"
    >
      <VIcon icon="tabler-search-off" size="40" class="mb-2 d-block mx-auto" />
      <p class="text-body-2">No permissions match "{{ search }}"</p>
    </div>
  </AppDialog>
</template>

<style scoped>
.abbr-field :deep(input) {
  text-transform: uppercase !important;
  letter-spacing: 1px !important;
  font-weight: 600 !important;
}
</style>
