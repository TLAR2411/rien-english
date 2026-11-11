<script setup>
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import { computed, onMounted, ref } from "vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import { getUsers } from "@/services/dataService";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import { api } from "@/utils/api";
import { useDialog } from "@/composables/useDialog";

definePage({
  meta: {
    title: "Activity Log",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-activity-log",
    layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const { t, locale } = useI18n();
const { showDialog: confirmDialog } = useDialog();

const dataTableRef = ref(null);
const isLoading = ref(true);
const showDialog = ref(false);
const selectedItem = ref(null);
const changes = ref([]);
const users = ref([]);

const showDeleteByDateDialog = ref(false);
const deleteByDateMode = ref("period"); // "period" | "date"
const selectedPeriod = ref(1);
const selectedDate = ref(null);

const periodOptions = [
  { title: "1 Month", value: 1 },
  { title: "2 Months", value: 2 },
  { title: "3 Months", value: 3 },
  { title: "6 Months", value: 6 },
  { title: "1 Year", value: 12 },
];

const deleteBeforeDate = computed(() => {
  if (deleteByDateMode.value === "date" && selectedDate.value) {
    return moment(selectedDate.value).format("YYYY-MM-DD");
  }
  return moment()
    .tz("Asia/Phnom_Penh")
    .subtract(selectedPeriod.value, "months")
    .format("YYYY-MM-DD");
});

const filter = ref({
  user_id: null,
  event: null,
  subject_type: null,
  search: null,
  start_date: moment().tz("Asia/Phnom_Penh").format("YYYY-MM-DD"),
});

const events = [
  { title: "Created", value: "created" },
  { title: "Updated", value: "updated" },
  { title: "Deleted", value: "deleted" },
];

const subjectTypes = [
  { title: "User", value: "User" },
];

const headers = [
  {
    title: t("Users"),
    key: "causer",
    value: (item) => item?.causer?.name_kh || null,
    visible: true,
  },
  { title: t("Event"), key: "event", visible: true },
  { title: t("Subject Type"), key: "subject_type", visible: true },
  { title: t("Subject Id"), key: "subject_id", visible: true },
  { title: t("Description"), key: "description", visible: true },
  {
    title: t("Created At"),
    key: "created_at",
    value: (item) =>
      moment(new Date(item.created_at)).format("DD-MM-YYYY HH:mm:ss"),
    visible: true,
  },
  { title: t("Action"), key: "actions", align: "center", fixed: true },
];

// Handle view action and compute changes
const onView = (item) => {
  selectedItem.value = item;
  if (item.event === "updated" && item.properties) {
    changes.value = computeChanges(
      item.properties.old,
      item.properties.attributes,
    );
  } else {
    changes.value = []; // Reset for non-update events
  }
  showDialog.value = true;
};

// Compute differences between old and new values
const computeChanges = (oldData, newData) => {
  const changesList = [];
  for (const key in oldData) {
    if (oldData[key] !== newData[key]) {
      changesList.push({
        field: key,
        oldValue: oldData[key],
        newValue: newData[key],
      });
    }
  }
  return changesList;
};

const onForceDelete = async (item) => {
  const confirmed = await confirmDialog({
    title: t("Force Delete Item?"),
    icon: "warning",
    confirmColor: "error",
    confirmText: "Delete",
  });
  if (!confirmed) return;
  try {
    isLoading.value = true;
    const res = await api.post("activity-log-force-delete", { id: item.id });
    if (res.data.status) {
      dataTableRef.value.reload();
    }
  } catch (error) {
    console.error("Failed to force delete:", error);
  } finally {
    isLoading.value = false;
  }
};

const onDeleteByDate = async () => {
  const confirmed = await confirmDialog({
    title: t("Delete all records before :date?", {
      date: deleteBeforeDate.value,
    }),
    icon: "warning",
    confirmColor: "error",
    confirmText: "Delete",
  });
  if (!confirmed) return;
  try {
    isLoading.value = true;
    const res = await api.delete(
      `activity-log-delete-by-date/${deleteBeforeDate.value}`,
    );
    if (res.data.status) {
      showDeleteByDateDialog.value = false;
      dataTableRef.value.reload();
    }
  } catch (error) {
    console.error("Failed to delete by date:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  const dataUsers = await getUsers();
  users.value = dataUsers;
});
</script>

<template>
  <AppCardTable
    enable-mobile-card
    ref="dataTableRef"
    title="Activity Log"
    title-icon="tabler-file-text-shield"
    api-url="activity-log-list"
    saveHeaderName="header-activity-log-list"
    saveStateName="save-state-activity-log-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    :headers="headers"
    is-filter
    is-view
    is-excel
    save-state
    :is-back="false"
    @on-view="onView"
  >
    <template #card-header>
      <VBtn
        color="error"
        variant="tonal"
        size="small"
        :aria-label="$t('Delete Old Records')"
        @click="showDeleteByDateDialog = true"
        class="delete-old-btn mr-2"
      >
        <VIcon icon="tabler-calendar-minus" size="18" />
        <span class="d-none d-sm-inline ms-2">{{ $t("Delete Old Records") }}</span>
      </VBtn>
    </template>
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="12" lg="2" md="3" sm="6">
          <AppAutocomplete
            v-model="filter.event"
            :items="events"
            item-title="title"
            item-value="value"
            clearable
          >
            <template v-slot:label>{{ $t("Event") }}</template>
          </AppAutocomplete>
        </VCol>
        <VCol cols="12" lg="2" md="3" sm="6">
          <AppAutocomplete
            v-model="filter.subject_type"
            :items="subjectTypes"
            item-title="title"
            item-value="value"
            clearable
          >
            <template v-slot:label>{{ $t("Subject Type") }}</template>
          </AppAutocomplete>
        </VCol>
        <VCol cols="12" lg="2" md="3" sm="6">
          <AppAutocomplete
            v-model="filter.user_id"
            :items="users"
            item-title="name_kh"
            item-value="id"
            clearable
          >
            <template v-slot:label>{{ $t("User") }}</template>
          </AppAutocomplete>
        </VCol>
        <VCol cols="12" lg="2" md="3" sm="6">
          <AppDateTimePicker v-model="filter.start_date" clearable>
            <template v-slot:label>{{ $t("Date") }}</template>
          </AppDateTimePicker>
        </VCol>
        <VCol cols="12" lg="2" md="3" sm="12">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            autocomlete="off"
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>

    <template v-slot:item.event="{ item }">
      <VChip color="success" v-if="item.event == 'created'" size="small">
        Created
      </VChip>
      <VChip color="warning" v-if="item.event == 'updated'" size="small">
        Updated
      </VChip>
      <VChip color="error" v-if="item.event == 'deleted'" size="small">
        Deleted
      </VChip>
    </template>

    <template v-slot:item.actions="{ item }">
      <VBtn
        icon="tabler-list-search"
        variant="text"
        color="primary"
        @click="onView(item)"
      />
      <VBtn
        icon="tabler-trash"
        variant="text"
        color="error"
        @click="onForceDelete(item)"
      />
    </template>
  </AppCardTable>

  <!-- Dialog for showing properties -->
  <VDialog v-model="showDialog" max-width="600px">
    <VCard>
      <VCardTitle>Record Details</VCardTitle>
      <VDivider />
      <VCardText style="padding: 12px">
        <div v-if="selectedItem">
          <div v-if="selectedItem.event === 'updated' && changes.length > 0">
            <VList lines="two" border>
              <template v-for="(change, index) in changes" :key="index">
                <VListItem>
                  <VListItemTitle>
                    <strong>{{ change.field }}</strong>
                  </VListItemTitle>
                  <VListItemSubtitle class="mt-1">
                    <span style="color: rgb(var(--v-theme-se))"
                      >old: {{ change.oldValue }} &nbsp;</span
                    >
                    <!-- <VIcon>tabler-arrow-right</VIcon> -->
                    <span style="color: rgb(var(--v-theme-success))"
                      >new: {{ change.newValue }}</span
                    >
                  </VListItemSubtitle>
                </VListItem>
                <VDivider v-if="index !== changes.length - 1" />
              </template>
            </VList>
          </div>
          <div v-else>
            <VList lines="two" border>
              <template
                v-for="(data, index) in selectedItem?.properties?.attributes"
              >
                <VListItem>
                  <VListItemTitle>
                    <strong>{{ index }}</strong>
                  </VListItemTitle>
                  <VListItemSubtitle class="mt-1"
                    ><span>{{ data }}</span></VListItemSubtitle
                  >
                </VListItem>

                <VDivider
                  v-if="
                    index !== selectedItem?.properties?.attributes.length - 1
                  "
                />
              </template>
              <!-- {{ selectedItem?.properties?.attributes }} -->
            </VList>
          </div>
        </div>
      </VCardText>
      <VCardText class="d-flex flex-row justify-end" style="padding: 12px">
        <VBtn color="secondary" variant="tonal" @click="showDialog = false"
          >Close</VBtn
        >
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Dialog for delete by date -->
  <VDialog v-model="showDeleteByDateDialog" max-width="420px" persistent>
    <VCard>
      <VCardTitle class="d-flex align-center ga-2 pt-4 px-4">
        <VIcon icon="tabler-calendar-minus" color="error" />
        {{ $t("Delete Old Records") }}
      </VCardTitle>
      <VDivider />
      <VCardText class="pt-4">
        <!-- <VBtnToggle
          v-model="deleteByDateMode"
          
          density="compact"
          class="mb-4 "
        >
          <VBtn value="period" size="small">{{ $t("By Period") }}</VBtn>
          <VBtn value="date" size="small">{{ $t("By Date") }}</VBtn>
        </VBtnToggle> -->

        <div v-if="deleteByDateMode === 'period'">
          <AppAutocomplete
            v-model="selectedPeriod"
            :items="periodOptions"
            item-title="title"
            item-value="value"
            hide-details
          >
            <template v-slot:label>{{ $t("Older Than") }}</template>
          </AppAutocomplete>
        </div>
        <div v-else>
          <AppDateTimePicker v-model="selectedDate" hide-details>
            <template v-slot:label>{{ $t("Delete Before Date") }}</template>
          </AppDateTimePicker>
        </div>

        <VAlert type="warning" variant="tonal" class="mt-4" density="compact">
          {{ $t("All records before") }}
          <strong>{{ deleteBeforeDate }}</strong>
          {{ $t("will be permanently deleted.") }}
        </VAlert>
      </VCardText>
      <VCardText class="d-flex justify-end ga-2 pb-4">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showDeleteByDateDialog = false"
          >{{ $t("Cancel") }}</VBtn
        >
        <VBtn color="error" @click="onDeleteByDate">{{ $t("Delete") }}</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
pre {
  background-color: #f5f5f5;
  padding: 10px;
  border-radius: 4px;
  overflow-x: auto;
}

/* On a phone the title and table actions leave room for the icon only */
@media (max-width: 599.98px) {
  .delete-old-btn {
    min-inline-size: 30px;
    padding-inline: 6px;
  }
}
</style>
