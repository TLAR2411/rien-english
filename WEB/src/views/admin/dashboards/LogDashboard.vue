<script setup>
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import { onMounted, ref } from "vue";
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import { getUsers } from "@/services/dataService";
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import formatDate from "@/utils/formater/formatDate";

definePage({
  meta: {
    title: "Activity Log",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-activity-log",
    // layoutWrapperClasses: "layout-content-height-fixed",
  },
});

const { t, locale } = useI18n();

const dataTableRef = ref(null);
const isLoading = ref(true);
const showDialog = ref(false); // For showing the dialog
const selectedItem = ref(null); // To store the selected log item
const changes = ref([]); // To store the list of changes
const users = ref([]);

const filter = ref({
  user_id: null,
  event: null,
  subject_type: null,
  search: null,
  start_date: moment().tz("Asia/Phnom_Penh").format("YYYY-MM-DD"),
});

const headers = [
  {
    title: t("Timestamp"),
    key: "timestamp",
    value: (item) => formatDate(item.timestamp),
    visible: true,
  },
  {
    title: t("Env"),
    key: "env",
    visible: true,
  },
  {
    title: t("Level"),
    key: "level",
    visible: true,
  },

  {
    title: t("Message"),
    key: "message",
    visible: true,
  },
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

onMounted(async () => {
  //   const dataUsers = await getUsers();
  //   users.value = dataUsers;
});

const getLevelColor = (level) => {
  const levelUpper = level?.toUpperCase() || "";

  const colors = {
    EMERGENCY: "error",
    ALERT: "error",
    CRITICAL: "error",
    ERROR: "error",
    WARNING: "warning",
    NOTICE: "primary",
    INFO: "info",
    DEBUG: "default",
  };

  return colors[levelUpper] || "default";
};
</script>

<template>
  <AppCardTable
    ref="dataTableRef"
    title="Laravel Log"
    title-icon="tabler-file"
    api-url="log-viewer-list"
    saveHeaderName="header-laravel-log-list"
    saveStateName="save-state-laravel-log-list"
    v-model:loading="isLoading"
    v-model:filters="filter"
    :headers="headers"
    is-filter
    is-view
    is-excel
    save-state
    :is-back="false"
    show-expand
    @on-view="onView"
    :is-full-height="false"
  >
    <template v-slot:item.level="{ item }">
      <VChip :color="getLevelColor(item.level)" size="small">
        {{ item.level }}
      </VChip>
    </template>

    <template v-slot:item.message="{ item }">
      <div
        style="
          max-width: 650px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        "
      >
        {{ item.message }}
      </div>
    </template>

    <template v-slot:expanded-row="{ item }">
      <tr>
        <td :colspan="headers.length + 1">
          <VCard flat class="ma-2">
            <VCardText>
              <div class="mb-3">
                <strong>Timestamp:</strong> {{ item.timestamp }}
              </div>
              <div class="mb-3">
                <strong>Environment:</strong> {{ item.env }}
              </div>
              <div class="mb-3">
                <strong>Level:</strong>
                <VChip :color="getLevelColor(item.level)" size="small">
                  {{ item.level }}
                </VChip>
              </div>
              <div>
                <strong>Full Message:</strong>
                <pre
                  class="mt-2 pa-3"
                  style="
                    background-color: #f5f5f5;
                    border-radius: 4px;
                    overflow-x: auto;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                  "
                  >{{ item.message }}</pre
                >
              </div>
            </VCardText>
          </VCard>
        </td>
      </tr>
    </template>
  </AppCardTable>
</template>

<style scoped>
pre {
  background-color: #f5f5f5;
  padding: 10px;
  border-radius: 4px;
  overflow-x: auto;
}
</style>
