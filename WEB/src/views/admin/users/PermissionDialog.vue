<script setup>
import { ref, computed, watch, toRefs } from "vue";
import { debounce } from "lodash";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";
import { requiredValidator } from "@/@core/utils/validators";

// Props definition
const props = defineProps({
  itemData: {
    type: Object,
    default: () => ({}),
  },
  userPermissions: {
    type: Array,
    default: () => [],
  },
  permissions: {
    type: Array,
    default: () => [],
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    required: false,
    skipCheck: true,
    default: undefined,
  },
});
const { itemData, permissions, userPermissions, isDialogVisible } =
  toRefs(props);
const openPanels = ref([]);

// Emits definition
const emit = defineEmits(["onCreate", "onUpdate", "update:isDialogVisible"]);

const itemDataCopy = ref({ ...itemData.value });
const permissionsSelected = ref({});

// Watch for changes in the itemData prop and update the local copy
watch(
  itemData,
  (newData) => {
    itemDataCopy.value = { ...newData };
  },
  { deep: true }
);

// Watch for changes in userPermissions and populate the permissionsSelected object
watch(
  userPermissions,
  (newValue) => {
    const newPermissionsObject = {};
    newValue.forEach((permission) => {
      newPermissionsObject[permission.id] = permission.id;
    });

    permissionsSelected.value = newPermissionsObject;
  },
  { immediate: true, deep: true }
);

// Resets the form data
const resetData = () => {
  itemDataCopy.value = {};
  // FIX: Reset to an empty object, not an array, to match the data structure.
  permissionsSelected.value = {};
};

// Submits the form data
const onFormSubmit = debounce(async (refForm) => {
  const { valid } = await refForm;
  if (valid) {
    const userId = itemDataCopy.value.user_id || null;
    const payload = {
      id: userId,
      permissions: permissionsSelected.value,
    };

    emit("onUpdate", payload, (res) => {
      if (res === 200) {
        resetData();
      }
    });
  }
}, 500);

// Closes the dialog and resets data
const onCloseDialog = () => {
  resetData();
  emit("update:isDialogVisible", false);
};

// Groups permissions by their 'group' property
const groupedPermission = computed(() => {
  if (!permissions.value.length) return {};
  return permissions.value.reduce((acc, item) => {
    const group = item.group || "Other";
    if (!acc[group]) {
      acc[group] = [];
    }
    acc[group].push(item);
    return acc;
  }, {});
});

const openAllPanels = async () => {
  await nextTick(); // ensure panels are rendered
  openPanels.value = Object.keys(groupedPermission.value); // ✅ use ids
};

// when dialog opens
watch(
  [() => isDialogVisible.value, () => groupedPermission.value],
  async ([visible, groups]) => {
    if (visible && groups && Object.keys(groups).length > 0) {
      await openAllPanels();
    } else if (!visible) {
      openPanels.value = []; // optional: collapse on close
    }
  },
  { immediate: true, flush: "post" }
);
/**
 * FIX: Manually handle checkbox state changes.
 * When a checkbox is checked, add the permission ID to the object.
 * When it's unchecked, remove the permission ID.
 * @param {boolean} isChecked - The new checked state of the checkbox.
 * @param {string|number} permissionId - The ID of the permission.
 */
const handleCheckboxChange = (isChecked, permissionId) => {
  if (isChecked) {
    permissionsSelected.value[permissionId] = permissionId;
  } else {
    delete permissionsSelected.value[permissionId];
  }
};

// Selects all permissions within a specific group
const selectAll = (group) => {
  if (groupedPermission.value[group]) {
    const updates = {};
    groupedPermission.value[group].forEach((permission) => {
      updates[permission.id] = permission.id;
    });
    permissionsSelected.value = { ...permissionsSelected.value, ...updates };
  }
};

// Unselects all permissions within a specific group
const unselectAll = (group) => {
  if (groupedPermission.value[group]) {
    // Create a new object to ensure reactivity is triggered reliably.
    const newPermissions = { ...permissionsSelected.value };
    groupedPermission.value[group].forEach((permission) => {
      delete newPermissions[permission.id];
    });
    permissionsSelected.value = newPermissions;
  } else {
    console.error("Group not found:", group);
  }
};

// Selects all permissions across all groups
const selectAllGlobal = () => {
  const allPermissions = {};
  for (const group in groupedPermission.value) {
    groupedPermission.value[group].forEach((permission) => {
      allPermissions[permission.id] = permission.id;
    });
  }
  permissionsSelected.value = allPermissions;
};

// Unselects all permissions
const unselectAllGlobal = () => {
  permissionsSelected.value = {};
};
</script>

<template>
  <AppAddEditDialog
    title="User Permission"
    icon="tabler-shield"
    :is-dialog-visible="isDialogVisible"
    :is-update="itemDataCopy.id != null ? true : false"
    :loading="loading"
    max-width="1000px"
    @on-close-dialog="onCloseDialog"
    @on-submit="onFormSubmit"
  >
    <VRow>
      <VCol>
        <VExpansionPanels multiple v-model="openPanels">
          <VCol>
            <VRow>
              <VBtn size="x-small" color="success" @click="selectAllGlobal()">
                Selected All
              </VBtn>
              <VBtn size="x-small" color="error" @click="unselectAllGlobal()">
                Unselected All
              </VBtn>
            </VRow>
          </VCol>
          <VExpansionPanel
            v-for="(groupPermissions, group) in groupedPermission"
            :key="group"
            :value="group"
          >
            <VExpansionPanelTitle class="text-primary">
              {{ group.toUpperCase() }}</VExpansionPanelTitle
            >
            <VExpansionPanelText>
              <VDivider />
              <VCol>
                <VRow
                  style="display: flex; align-items: center; flex-wrap: wrap"
                >
                  <VBtn
                    size="x-small"
                    color="success"
                    @click="selectAll(group)"
                    class="mr-2 mb-2"
                  >
                    Select All
                  </VBtn>
                  <VBtn
                    size="x-small"
                    color="error"
                    @click="unselectAll(group)"
                    class="mr-2 mb-2"
                  >
                    Unselect All
                  </VBtn>
                  <template v-for="item in groupPermissions" :key="item.id">
                    <!-- 
                      FIX: Use :model-value for one-way data binding and @update:model-value 
                      to trigger a custom handler. This gives us full control over what happens 
                      when the checkbox is checked or unchecked.
                    -->
                    <VCheckbox
                      :model-value="!!permissionsSelected[item.id]"
                      :label="item.display_name"
                      @update:model-value="
                        (isChecked) => handleCheckboxChange(isChecked, item.id)
                      "
                      class="mr-2"
                    />
                  </template>
                </VRow>
              </VCol>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </VCol>
    </VRow>
  </AppAddEditDialog>
</template>
