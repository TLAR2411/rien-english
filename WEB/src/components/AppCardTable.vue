<script setup>
import { debounce } from "lodash";
import {
  computed,
  ref,
  watch,
  onMounted,
  useAttrs,
  useId,
  useSlots,
  nextTick,
} from "vue";
import setTableState from "@/utils/datatable/setTableState";
import getTableState from "@/utils/datatable/getTableState";
import { api } from "@/utils/api";
import hasPermission from "@/utils/hasPermission.js";
import { useI18n } from "vue-i18n";
import { useSettingStore } from "@/stores/settingStore";
import { useDialog } from "@/composables/useDialog";
import { useRoute, useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import moment from "moment-timezone";
import {
  useRowActions,
  dataTableProps,
  dataTableEmits,
  getButtonColor,
  getButtonTitle,
  getButtonIcon,
  getButtonDisable,
  checkPermission,
} from "@/composables/useRowActions";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";

const { isLeftSidebarOpen } = useResponsiveLeftSidebar();
const DEBOUNCE_DELAY = 300;
const { showDialog } = useDialog();
const router = useRouter();
const { mdAndUp, name: displayBreakpoint } = useDisplay();

const props = defineProps({
  ...dataTableProps,
  isReload: Boolean,
  isLeftSidebar: Boolean,
  canCreate: String,
  isBack: Boolean,
  createPage: String,
  createDialog: Boolean,
  isDialogCreateVisible: Boolean,
  isHeader: { type: Boolean, default: true },
  // Totals row (headers with `sum`) shown or hidden; v-model:show-sum works too
  showSum: { type: Boolean, default: false },
  isTitle: { type: Boolean, default: true },
  title: { type: String, default: "DataTable" },
  titleIcon: { type: String, default: "tabler-code" },
  headers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
  loading: Boolean,
  isCheckBranch: Boolean,
  hideHeader: Boolean,
  hideFooter: Boolean,
  isToggleColumn: { type: Boolean, default: true },
  limit: { type: Number, default: 25 },
  isHover: { type: Boolean, default: true },
  isPagination: { type: Boolean, default: true },
  disableSort: { type: Boolean, default: true },
  isExcel: Boolean,
  saveState: Boolean,
  isFilter: Boolean,
  repsonce: { type: Object, default: () => ({}) },
  showFilters: Boolean,
  isFullHeight: { type: Boolean, default: true },
  isFullHeightTab: Boolean,
  apiUrl: String,
  apiData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  border: { type: String, default: "border" },
  transformData: { type: Function, required: false, default: null },
  selectedItemHeader: { type: Object, default: () => ({}) },
  saveHeaderName: String,
  saveStateLeftName: String,
  saveStateName: String,
  variant: { type: String, default: "tonal" },
  showExpand: { type: Boolean, default: false },
  expandByDefault: { type: Boolean, default: false },
  itemValue: { type: String, default: "id" },
  isFixedColumnRight: { type: Boolean, default: true },
  enableMobileCard: { type: Boolean, default: false },
  mobileBreakpoint: { type: String, default: "xs" },
  // declared rather than left to fallthrough: the mobile card view renders its
  // own checkboxes and needs to read and write the selection itself
  modelValue: { type: Array, default: () => [] },
  showSelect: { type: Boolean, default: false },
  // (item) => a Vuetify theme colour name, drawn as a dot on the mobile card
  // so a row's status is readable before it is expanded
  mobileCardAccent: { type: Function, default: null },
  disableSelect: { type: Boolean, default: false },
  mobileWidth: { type: Number, default: 678 },
  isDoubleClickAction: { type: Boolean, default: true },
  doubleClickDelay: { type: Number, default: 400 },
});
const isMobile = ref(window.innerWidth < props.mobileWidth);
const internalLoading = ref(false);
const { t } = useI18n();
const settingStore = useSettingStore();

const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id;
  const _id = useId();
  return _elementIdToken ? `app-datatable-${_elementIdToken}` : _id;
});
const expandedRows = ref([]); // For desktop
const mobileExpanded = ref([]); // For mobile toggles

const mobileCardAccentStyle = (item) => {
  if (!props.mobileCardAccent) return null;

  const color = props.mobileCardAccent(item);
  if (!color) return null;

  return { backgroundColor: `rgb(var(--v-theme-${color}))` };
};

// selection helpers for the mobile card view; the desktop table keeps using
// VDataTable's own checkbox column
const isRowSelected = (item) =>
  props.modelValue.includes(item[props.itemValue]);

const toggleRowSelection = (item) => {
  const key = item[props.itemValue];

  emit(
    "update:modelValue",
    isRowSelected(item)
      ? props.modelValue.filter((value) => value !== key)
      : [...props.modelValue, key],
  );
};

// "select all" for the mobile card view, over the rows currently loaded;
// selections outside them (e.g. another page) are left alone
const loadedRowKeys = computed(() =>
  (dataItems.value?.data || []).map((item) => item[props.itemValue]),
);

const selectedRowCount = computed(
  () =>
    loadedRowKeys.value.filter((key) => props.modelValue.includes(key)).length,
);

const isAllRowsSelected = computed(
  () =>
    loadedRowKeys.value.length > 0 &&
    selectedRowCount.value === loadedRowKeys.value.length,
);

const isSomeRowsSelected = computed(
  () => selectedRowCount.value > 0 && !isAllRowsSelected.value,
);

const toggleSelectAllRows = () => {
  const keys = loadedRowKeys.value;

  emit(
    "update:modelValue",
    isAllRowsSelected.value
      ? props.modelValue.filter((key) => !keys.includes(key))
      : [...new Set([...props.modelValue, ...keys])],
  );
};

const toggleMobileRow = (id) => {
  if (mobileExpanded.value.includes(id)) {
    mobileExpanded.value = mobileExpanded.value.filter((item) => item !== id);
  } else {
    mobileExpanded.value.push(id);
  }
};

// Triple-click on a row opens the same action menu as the "..." button.
const rowMenu = ref(false);
const rowMenuTarget = ref([0, 0]);
const rowMenuItem = ref(null);
let rowClickCount = 0;
let rowClickTimer = null;
let rowClickId = null;

const resetRowClicks = () => {
  clearTimeout(rowClickTimer);
  rowClickTimer = null;
  rowClickCount = 0;
  rowClickId = null;
};

const openRowMenu = (event, item) => {
  rowMenuItem.value = item;
  rowMenuTarget.value = [event.clientX, event.clientY];
  // Re-open so the menu re-anchors when it is already showing for another row.
  rowMenu.value = false;
  nextTick(() => {
    rowMenu.value = true;
  });
};

const onRowClick = (event, { item } = {}) => {
  if (!props.isDoubleClickAction || !item) return;
  // Let the row's own controls (buttons, links, checkboxes, inputs) win.
  if (
    event.target?.closest?.(
      "button, a, input, textarea, select, .v-btn, .v-selection-control, .v-input",
    )
  )
    return;

  const id = item[props.itemValue];
  if (rowClickId !== id) resetRowClicks();
  rowClickId = id;
  rowClickCount += 1;
  clearTimeout(rowClickTimer);

  if (rowClickCount >= 2) {
    resetRowClicks();
    // A triple click also selects the row text in the browser.
    window.getSelection?.()?.removeAllRanges();
    if (!getRowActions(item).length) return;
    // Same rule as the ⋮ button: no menu for rows that hide their actions
    if (props.isMoreActionCondition && !props.isMoreActionCondition(item))
      return;
    openRowMenu(event, item);
    return;
  }

  rowClickTimer = setTimeout(resetRowClicks, props.doubleClickDelay);
};

const savedOptions = props.saveState
  ? getTableState(props.saveStateName)
  : null;
const options = ref(savedOptions || { page: 1, limit: props.limit });
const filters = ref(savedOptions?.filter || { ...(props?.filters || []) });
const showFilters = ref(
  savedOptions?.showFilter || (props?.showFilters ? [0] : []),
);
const headerVisible = ref(savedOptions?.showHeader || props.isHeader);
// Totals row on / off (toolbar Σ button), remembered with the table state
// Starts from the `showSum` prop (also v-model:show-sum); the user's last
// choice wins when the table saves its state
const sumVisible = ref(savedOptions?.showSum ?? props.showSum);
watch(
  () => props.showSum,
  (v) => {
    sumVisible.value = v;
  },
);
const serverTotals = ref(null);
const rightLog = ref();
const isFilterOpen = ref(
  Array.isArray(showFilters.value)
    ? showFilters.value.includes(0)
    : !!showFilters.value,
);
const renderFilterContent = ref(isFilterOpen.value);

const toggleFilters = async () => {
  const open = !isFilterOpen.value;
  isFilterOpen.value = open;
  if (open) {
    renderFilterContent.value = true;
    await nextTick();
  }
  showFilters.value = open ? [0] : [];
  saveState();
};

const saveState = () => {
  if (props.saveStateName && props.saveState) {
    setTableState(
      props.saveStateName,
      {
        page: options.value.page,
        limit: options.value.limit,
        filter: filters?.value || [],
        showFilter: showFilters?.value || [],
        showHeader: headerVisible.value,
        showSum: sumVisible.value,
      },
      30,
    );
  }
};

const emit = defineEmits([
  ...dataTableEmits,
  "update:modelValue",
  "update:isDialogCreateVisible",
  "exportToExcel",
  "update:response",
  "update:showSum",
  "update:loading",
  "update:filters",
  "update:headerCount",
]);

const normalizePermissionFlags = (raw) => raw;
const { actionButtons } = useRowActions(emit, normalizePermissionFlags(props), {
  t,
  showDialog,
  debounce,
  DEBOUNCE_DELAY,
});

// Every action available for a row, whether it renders as an inline button or
// a "..." menu entry — the triple-click menu collects them all.
const getRowActions = (item) =>
  actionButtons.filter(
    (button) =>
      button.show &&
      (!button.permission || checkPermission(button, item)) &&
      (!button.condition || button.condition(item)),
  );

const dataItems = ref([]);
watch(
  () => props.loading,
  (n) => {
    if (n !== undefined) internalLoading.value = n;
  },
);

const calculateLength = (total, limit) => {
  if (!total || !limit) return 0;
  return Math.ceil(total / limit);
};

const isMobileView = computed(() => {
  if (!props.enableMobileCard) return false;
  const breakpoints = ["xs", "sm", "md", "lg", "xl", "xxl"];
  const currentIdx = breakpoints.indexOf(displayBreakpoint.value);
  const targetIdx = breakpoints.indexOf(props.mobileBreakpoint);
  return currentIdx <= targetIdx;
});

const getValue = (item, key) => {
  const keys = key.split(".");
  let value = item;
  for (const k of keys) {
    value = value ? value[k] : null;
  }
  return value || "";
};

const getRenderValue = (item, header) => {
  if (typeof header.value === "function") return header.value(item);
  if (typeof header.value === "string") return getValue(item, header.value);
  return getValue(item, header.key);
};

const mobileHeaders = computed(() => {
  const visible = headers.value.filter(
    (h) => h.key !== "actions" && h.visible !== false,
  );
  // a header can opt in as the card's headline or its summary value; with no
  // flags set this falls back to the first two visible columns, as before
  const first = visible.find((h) => h.mobilePrimary) || visible[0] || null;
  const second =
    visible.find((h) => h.mobileSummary && h !== first) ||
    visible.find((h) => h !== first) ||
    null;

  // `mobileFront` columns are shown on the face of the card as a small
  // label/value grid, so the key facts are readable without expanding it
  const front = visible.filter(
    (h) => h.mobileFront && h !== first && h !== second,
  );

  return {
    first,
    second,
    front,
    others: visible.filter(
      (h) => h !== first && h !== second && !front.includes(h),
    ),
  };
});

const initData = async (item) => {
  internalLoading.value = true;
  emit("update:loading", true);
  if (props.expandByDefault) expandedRows.value = [];
  try {
    if (props.apiUrl != null) {
      const res = await api.get(props.apiUrl, {
        params: {
          ...props.apiData,
          page: options.value.page,
          limit: options.value.limit,
          filter: filters?.value || [],
        },
      });
      emit("update:response", res.data);
      let items = res.data.data.data;
      if (props.transformData) items = props.transformData(items);
      dataItems.value = {
        data: items,
        links: res.data.data.links,
        meta: res.data.data.meta,
      };
      // Optional grand totals for the totals row: { column key: number }
      serverTotals.value = res.data.data.totals ?? res.data.totals ?? null;
      if (props.expandByDefault)
        expandedRows.value = dataItems.value.data.map(
          (item) => item[props.itemValue],
        );
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    internalLoading.value = false;
    emit("update:loading", false);
  }
};

onMounted(() => {
  emit("update:filters", filters.value || {});
  initData();
});

const getButtonBackgroundClass = (button, item) => `${button.color}-bg`;
const dataTableRef = ref(null);

const exportToExcel = async () => {
  // xlsx is ~400 KB; load it only when someone actually exports
  const XLSX = await import("xlsx");
  const rows = (dataItems.value?.data || []).map((item) => {
    const row = {};
    exportHeaders.value.forEach((header) => {
      const title = header.title;
      let val = header.exportValue
        ? header.exportValue(item)
        : header.value && typeof header.value === "function"
          ? header.value(item)
          : getValue(item, header.key);
      if (typeof val === "string" && /^[\d,]+(\.\d+)?$/.test(val))
        val = val.replace(/,/g, "");
      if (!isNaN(val) && val !== "") val = Number(val);
      row[title] = val == null ? "" : val;
    });
    return row;
  });
  const ws = XLSX.utils.json_to_sheet(rows, { skipHeader: false });
  const keys = Object.keys(rows[0] || {});
  ws["!cols"] = keys.map((k) => {
    const max = Math.max(
      k.length,
      ...rows.map((r) => (r[k] ? String(r[k]).length : 0)),
    );
    return { wch: Math.min(Math.max(max + 2, 10), 60) };
  });
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
  XLSX.writeFile(
    wb,
    `Excel-${useSettingStore().branch_abbr}-${moment().format(
      "YYYY-MM-DD HH:mm:ss",
    )}.xlsx`,
  );
};

watch(
  () => filters.value,
  debounce(() => {
    options.value.page = 1;
    saveState();
    initData();
  }, DEBOUNCE_DELAY),
  { deep: true },
);
watch(
  () => ({
    page: options.value.page,
    branchId: settingStore.branch_id,
    limit: options.value.limit,
  }),
  (n, o) => {
    if (n.page !== o.page || n.limit !== o.limit || n.branchId !== o.branchId) {
      saveState();
      initData();
    }
  },
);

const savedHeader = props.saveState
  ? getTableState(props.saveHeaderName)
  : null;
function mergeSavedHeaders(original, saved) {
  return original.map((origCol) => {
    const savedCol = saved?.find((h) => h.key === origCol.key);
    if (!savedCol) return origCol;
    // Totals options always come from the code, never from saved state
    const { sum, sumKey, sumFormat, value, ...savedRest } = savedCol;
    return { ...origCol, ...savedRest };
  });
}
const headers = ref(mergeSavedHeaders(props.headers, savedHeader));
// The page changed its headers (e.g. added `sum`): take the new definitions,
// keep what the user chose (shown / hidden columns)
watch(
  () => props.headers,
  (n) => {
    headers.value = mergeSavedHeaders(n, headers.value);
  },
);
const visibleHeaders = computed(() =>
  headers?.value.filter((h) => h.visible !== false),
);
const menu = ref(false);

// ---------- Totals row ----------
// A header opts in with `sum: true` (adds up its key) or
// `sum: (item) => number`. Optional: `sumKey` (key in the server totals) and
// `sumFormat: (total) => string`.
// The row shows the server's grand total when the response carries
// `totals[key]` (every row matching the filters); otherwise the total of the
// rows on this page, and says so in its label.
const hasSum = computed(() => headers.value.some((h) => h.sum));
const sumHeaders = computed(() => visibleHeaders.value.filter((h) => h.sum));
const headerOf = (column) => visibleHeaders.value.find((h) => h.key === column?.key);
const sumKey = (header) => header.sumKey || header.key;

const toNumber = (v) => {
  const n = Number(String(v ?? "").replace(/,/g, ""));
  return Number.isFinite(n) ? n : 0;
};

const serverTotal = (header) => {
  const v = serverTotals.value?.[sumKey(header)];
  return v === undefined || v === null ? null : toNumber(v);
};

const sumOf = (header) => {
  const fromServer = serverTotal(header);
  if (fromServer !== null) return fromServer;
  return (dataItems.value?.data || []).reduce((total, item) => {
    if (typeof header.sum === "function") return total + toNumber(header.sum(item));
    const raw = getValue(item, header.key);
    // No raw field under the key: fall back to the cell's own value
    return total + toNumber(raw === "" && typeof header.value === "function" ? header.value(item) : raw);
  }, 0);
};

const sumText = (header) =>
  typeof header.sumFormat === "function"
    ? header.sumFormat(sumOf(header))
    : sumOf(header).toLocaleString("en-US", { maximumFractionDigits: 2 });

// Grand total only when every summed column got a server total
const isGrandTotal = computed(
  () => sumHeaders.value.length > 0 && sumHeaders.value.every((h) => serverTotal(h) !== null),
);
const sumLabel = computed(() => t(isGrandTotal.value ? "Total" : "Page total"));

// The label goes in the first ordinary column (not select / expand / a sum / actions)
const sumLabelIndex = (columns) =>
  columns.findIndex((c) => {
    const header = headerOf(c);
    return header && !header.sum && header.key !== "actions";
  });

const isSumVisible = computed(
  () => sumVisible.value && sumHeaders.value.length > 0 && (dataItems.value?.data?.length || 0) > 0,
);
const toggleSum = () => {
  sumVisible.value = !sumVisible.value;
  emit("update:showSum", sumVisible.value);
  saveState();
};

watch(
  headers,
  (n) => {
    const cleanHeaders = headers.value.map(
      ({ value, sum, sumKey, sumFormat, ...rest }) => ({
        ...rest,
      }),
    );
    setTableState(
      props.saveHeaderName,
      cleanHeaders,
      1000 * 60 * 60 * 24 * 365,
    );
  },
  { deep: true },
);

function create() {
  if (props.isCheckBranch && settingStore.branch_id === "*") {
    showDialog({ title: "សូមជ្រើសរើសសាខា!", icon: "error", isConfirm: false });
    return;
  }

  if (props?.createPage) router.push({ name: props.createPage });
  else if (props?.createDialog)
    emit("update:isDialogCreateVisible", !props.isDialogCreateVisible);
}
const onBack = () => router.back();
const exportHeaders = computed(() =>
  headers.value.filter((header) => !header.excludeFromExport),
);
watch(headerVisible, (n, o) => saveState());
const reload = debounce(() => initData(), 500);

// Slots passed straight through to VDataTable. "no-data", "actions-prepend"
// and "footer-actions" are left out because they are rendered by explicit
// templates here (defining a slot twice crashes).
const slots = useSlots();
const forwardedSlotNames = computed(() =>
  Object.keys(slots).filter(
    (name) =>
      ![
        "no-data",
        "actions-prepend",
        "footer-actions",
        "table-top",
        "sum-actions",
      ].includes(name),
  ),
);

defineExpose({ reload, exportToExcel });
</script>

<template>
  <div
    :class="
      isFullHeightTab ? 'list-tab-height' : isFullHeight ? 'list-height' : ''
    "
  >
    <VLayout
      class="app-card-layout"
      style="z-index: 0"
      :class="[
        // under a tab bar the outer div sets the height; fill it
        isFullHeightTab ? 'h-100' : isFullHeight ? 'list-height' : '',
        border,
      ]"
    >
      <VNavigationDrawer
        v-if="isLeftSidebar"
        v-model="isLeftSidebarOpen"
        data-allow-mismatch
        absolute
        touchless
        location="start"
        :width="isMobile ? 300 : 370"
        :temporary="$vuetify.display.smAndDown"
        class="chat-list-sidebar"
        :permanent="$vuetify.display.mdAndUp"
      >
        <div
          ref="leftLog"
          class="flex-grow-1 chat-log-container h-100"
          style="background-color: white; overflow-y: auto"
        >
          <div class="table-wrapper h-100 d-flex flex-column">
            <slot name="left-sidebar"></slot>
          </div>
        </div>

        <template #append>
          <VDivider />
          <VForm
            class="chat-log-message-form"
            style="min-block-size: 52px !important"
          >
            <VCol style="background-color: white" class="pa-0">
              <VCardText style="padding: 0">
                <div
                  class="d-flex flex-wrap justify-center justify-sm-space-between align-center"
                  style="min-block-size: 54px !important"
                >
                  <slot name="left-sidebar-footer"></slot>
                </div>
              </VCardText>
            </VCol>
          </VForm>
        </template>
      </VNavigationDrawer>

      <VMain class="chat-content-container">
        <div class="d-flex flex-column" :class="[isFullHeight ? 'h-100' : '']">
          <template v-if="isHeader">
            <VCardItem
              v-slot:title
              style="
                padding: 0.5rem;
                background-color: rgba(211, 211, 211, 0.2);
              "
            >
              <div
                style="
                  display: flex;
                  justify-content: space-between;
                  align-items: center;
                  height: 38px;
                  gap: 1rem;
                "
              >
                <h5 v-if="isTitle">
                  <div class="d-flex align-center text-primary">
                    <IconBtn
                      v-if="isLeftSidebar"
                      class="d-lg-none mr-2"
                      @click="isLeftSidebarOpen = true"
                      rounded
                      size="30"
                      :aria-label="$t('Menu')"
                    >
                      <VIcon icon="tabler-menu-2" />
                    </IconBtn>
                    <VAvatar
                      :icon="titleIcon"
                      rounded
                      color="primary"
                      variant="tonal"
                    />
                    <VCardTitle class="ml-2 text-primary"
                      ><span class="text-primary">{{
                        $t(title)
                      }}</span></VCardTitle
                    >
                  </div>
                </h5>
                <div
                  class="w-100 d-flex justify-end align-center scrollable-actions"
                >
                  <slot name="card-header"></slot>

                  <IconBtn
                    rounded
                    v-if="isBack"
                    size="30"
                    class="mr-1"
                    :aria-label="$t('Back')"
                    @click.prevent="onBack"
                    ><VIcon icon="tabler-arrow-left" size="20"
                  /></IconBtn>
                  <IconBtn
                    rounded
                    v-if="isReload"
                    size="30"
                    class="mr-1"
                    :aria-label="$t('Reload')"
                    @click.prevent="reload"
                    :loading="loading"
                    ><VIcon icon="tabler-refresh" size="20"
                  /></IconBtn>
                  <IconBtn
                    rounded
                    size="30"
                    class="mr-1"
                    v-if="isExcel"
                    :aria-label="$t('Excel')"
                    @click.prevent="exportToExcel"
                    ><VIcon icon="tabler-file-spreadsheet" size="20"
                  /></IconBtn>
                  <!-- Totals row on / off -->
                  <IconBtn
                    rounded
                    size="30"
                    class="mr-1"
                    v-if="hasSum"
                    :aria-label="$t(sumVisible ?'Hide totals' : 'Show totals')"
                    @click.prevent="toggleSum"
                    ><VIcon
                      :icon="sumVisible ? 'tabler-layout-bottombar-filled' : 'tabler-layout-bottombar-inactive'"
                      size="20"
                      :color="sumVisible ?'primary' : undefined" /><VTooltip
                      activator="parent"
                      location="bottom"
                      open-delay="400"
                      >{{ $t(sumVisible ?"Hide totals" : "Show totals") }}</VTooltip
                    ></IconBtn
                  >
                  <VMenu
                    v-if="isToggleColumn"
                    v-model="menu"
                    :close-on-content-click="false"
                    scroll-strategy="none"
                    eager
                    :content-class="'filter-menu-content'"
                    location="left"
                  >
                    <template #activator="{ props }"
                      ><IconBtn
                        rounded
                        v-bind="props"
                        size="30"
                        class="mr-2"
                        :aria-label="$t('Toggle Columns')"
                        ><VIcon icon="tabler-columns-3" size="20" /></IconBtn
                    ></template>
                    <VCard min-width="300" class="pb-4">
                      <VList
                        ><VListItem :title="$t('Toggle Columns')"
                          ><template v-slot:prepend
                            ><VIcon
                              icon="tabler-columns-3"
                            ></VIcon></template></VListItem
                      ></VList>
                      <VDivider />
                      <VList
                        v-for="(header, index) in headers"
                        :key="header?.key"
                        class="pt-0 pb-0"
                        ><VListItem
                          v-if="header?.key !== 'actions'"
                          style="padding-bottom: 0 !important"
                          ><VSwitch
                            v-model="header.visible"
                            :label="header.title"
                            color="primary"
                            hide-details /></VListItem
                      ></VList>
                    </VCard>
                  </VMenu>
                  <IconBtn
                    rounded
                    @click.prevent="toggleFilters"
                    size="30"
                    class="mr-2"
                    v-if="isFilter"
                    :aria-label="$t('Filter')"
                    ><VIcon
                      :icon="
                        showFilters[0] == 0
                          ? 'tabler-filter-up'
                          : 'tabler-filter-down'
                      "
                      size="20"
                  /></IconBtn>
                  <VBtn
                    size="30"
                    v-if="
                      (createDialog || createPage) &&
                      (!canCreate || hasPermission(canCreate))
                    "
                    @click.prevent="create"
                    variant="tonal"
                    ><VIcon icon="tabler-plus" size="20"
                  /></VBtn>
                </div>
              </div>
            </VCardItem>
            <VDivider />
          </template>

          <div id="app-card-filter-panel" v-if="isHeader">
            <VExpandTransition
              ><div v-if="renderFilterContent && isFilterOpen">
                <div class="pa-2 ma-1"><slot name="filter"></slot></div>
                <VDivider /></div
            ></VExpandTransition>
          </div>

          <!-- optional page content right above the rows (e.g. a summary of
               what the filters selected) -->
          <slot name="table-top" />

          <!-- stands in for the desktop table's header checkbox; sits outside
               the scrolling list so it is always in view without sticky -->
          <div
            v-if="
              isMobileView &&
              showSelect &&
              !internalLoading &&
              dataItems.data?.length
            "
            class="mobile-select-all d-flex align-center cursor-pointer"
            @click="!disableSelect && toggleSelectAllRows()"
          >
            <VCheckboxBtn
              :model-value="isAllRowsSelected"
              :indeterminate="isSomeRowsSelected"
              :disabled="disableSelect"
              density="compact"
              hide-details
              class="flex-grow-0 mr-2"
              @click.stop
              @update:model-value="toggleSelectAllRows"
            />
            <span class="mobile-select-all__label">{{ $t("Select All") }}</span>
            <VSpacer />
            <VChip
              size="small"
              :color="selectedRowCount ? 'primary' : undefined"
              :variant="selectedRowCount ? 'tonal' : 'outlined'"
            >
              {{ selectedRowCount }} / {{ dataItems.data.length }}
            </VChip>
          </div>

          <div
            ref="rightLog"
            class="flex-grow-1 chat-log-container"
            style="background-color: white; overflow-y: auto"
          >
            <div
              v-if="isMobileView"
              class="mobile-card-container pa-3 table-scroll-wrapper"
              :class="[isFullHeight ? 'h-100' : '']"
            >
              <template v-if="internalLoading">
                <div class="text-center pa-5">
                  <VProgressCircular
                    indeterminate
                    color="primary"
                    aria-label="Loading progress Card Table"
                    role="progressbar"
                  ></VProgressCircular>
                </div>
              </template>
              <template
                v-else-if="!dataItems.data || dataItems.data.length === 0"
              >
                <slot name="no-data">
                  <div class="no-data-container text-center py-4">
                    <VIcon
                      icon="tabler-info-circle"
                      size="48"
                      color="warning"
                      class="mb-2"
                    />
                    <p class="text-medium-emphasis">
                      {{ $t("No Records Found") }}
                    </p>
                  </div>
                </slot>
              </template>
              <template v-else>
                <div
                  v-for="(item, index) in dataItems.data"
                  :key="item[itemValue] || index"
                  class="mb-3"
                >
                  <VCard border elevation="1" class="rounded-lg">
                    <!-- status dot in the corner; VCard is position:relative -->
                    <span
                      v-if="mobileCardAccentStyle(item)"
                      class="mobile-card-dot"
                      :style="mobileCardAccentStyle(item)"
                    />
                    <div
                      class="d-flex justify-space-between align-center px-4 py-3 cursor-pointer"
                      @click="toggleMobileRow(item[itemValue])"
                      v-ripple
                    >
                      <!-- .stop so ticking a row does not also expand it -->
                      <VCheckboxBtn
                        v-if="showSelect"
                        :model-value="isRowSelected(item)"
                        :disabled="disableSelect"
                        density="compact"
                        hide-details
                        class="flex-grow-0 mr-2"
                        @click.stop
                        @update:model-value="toggleRowSelection(item)"
                      />

                      <div
                        class="d-flex flex-column flex-grow-1"
                        style="min-width: 0"
                      >
                        <slot
                          v-if="mobileHeaders.first"
                          :name="`item.${mobileHeaders.first.key}`"
                          :item="item"
                          :index="index"
                          :value="getRenderValue(item, mobileHeaders.first)"
                        >
                          <span class="mobile-card-long-text font-weight-bold">{{
                            getRenderValue(item, mobileHeaders.first)
                          }}</span>
                        </slot>
                      </div>

                      <div class="mobile-card-side d-flex align-center justify-end ml-2">
                        <div class="mobile-card-side__value text-end mr-2">
                          <slot
                            v-if="mobileHeaders.second"
                            :name="`item.${mobileHeaders.second.key}`"
                            :item="item"
                            :index="index"
                            :value="getRenderValue(item, mobileHeaders.second)"
                          >
                            <div class="d-flex flex-column">
                              <span style="font-size: 0.8125rem">{{
                                mobileHeaders.second.title
                              }}</span>
                              <span class="font-weight-bold text-primary">{{
                                getRenderValue(item, mobileHeaders.second)
                              }}</span>
                            </div>
                          </slot>
                        </div>
                        <VIcon
                          :icon="
                            mobileExpanded.includes(item[itemValue])
                              ? 'tabler-chevron-up'
                              : 'tabler-chevron-down'
                          "
                          color="grey-lighten-1"
                          class="flex-shrink-0"
                        />
                      </div>
                    </div>

                    <div
                      v-if="mobileHeaders.front.length"
                      class="mobile-card-front cursor-pointer"
                      @click="toggleMobileRow(item[itemValue])"
                    >
                      <div
                        v-for="header in mobileHeaders.front"
                        :key="header.key"
                        class="mobile-card-front__cell"
                      >
                        <span class="mobile-card-front__label">{{
                          header.title
                        }}</span>
                        <span class="mobile-card-front__value">
                          <slot
                            :name="`item.${header.key}`"
                            :item="item"
                            :index="index"
                            :value="getRenderValue(item, header)"
                          >
                            {{ getRenderValue(item, header) || "-" }}
                          </slot>
                        </span>
                      </div>
                    </div>

                    <VExpandTransition>
                      <div v-show="mobileExpanded.includes(item[itemValue])">
                        <VDivider />
                        <div class="bg-grey-lighten-5 px-4 py-3">
                          <div
                            v-for="header in mobileHeaders.others"
                            :key="header.key"
                            class="d-flex justify-space-between align-center gap-3 mb-2 pb-1 border-b"
                            style="
                              border-bottom: 1px dashed rgba(0, 0, 0, 0.08) !important;
                            "
                          >
                            <span
                              class="text-medium-emphasis flex-shrink-0"
                              style="font-size: 0.8125rem"
                              >{{ header.title }}</span
                            >
                            <span
                              class="mobile-card-long-text font-weight-medium text-end"
                              style="font-size: 0.8125rem"
                            >
                              <slot
                                :name="`item.${header.key}`"
                                :item="item"
                                :index="index"
                                :value="getRenderValue(item, header)"
                              >
                                {{ getRenderValue(item, header) }}
                              </slot>
                            </span>
                          </div>
                        </div>
                      </div>
                    </VExpandTransition>

                    <div
                      v-if="visibleHeaders.find((h) => h.key === 'actions')"
                      class="px-2 py-2 border-t bg-surface"
                    >
                      <div class="d-flex justify-end align-center gap-2">
                        <slot name="item.actions" :item="item" :index="index">
                          <template
                            v-for="(button, btnIndex) in actionButtons"
                            :key="btnIndex"
                          >
                            <VBtn
                              v-if="
                                !button.isDelete &&
                                button.show &&
                                (!button.permission ||
                                  checkPermission(button, item)) &&
                                (!button.condition || button.condition(item))
                              "
                              :color="getButtonColor(button, item)"
                              :disabled="getButtonDisable(button, item)"
                              variant="tonal"
                              size="small"
                              class="px-3"
                              @click.stop="() => button.action(item)"
                            >
                              <VIcon
                                start
                                :icon="getButtonIcon(button, item)"
                                size="16"
                              />
                              {{ $t(getButtonTitle(button, item)) }}
                            </VBtn>
                          </template>

                          <IconBtn
                            v-if="
                              isMoreAction &&
                              (!isMoreActionCondition ||
                                isMoreActionCondition(item))
                            "
                            size="small"
                            color="medium-emphasis"
                            :aria-label="$t('More actions')"
                          >
                            <VIcon icon="tabler-dots-vertical" />
                            <VMenu activator="parent">
                              <VList>
                                <template
                                  v-for="(button, index) in actionButtons"
                                  :key="index"
                                >
                                  <VListItem
                                    v-if="
                                      !button.btn &&
                                      button.show &&
                                      (!button.permission ||
                                        checkPermission(button, item)) &&
                                      (!button.condition ||
                                        button.condition(item))
                                    "
                                    @click="() => button.action(item)"
                                  >
                                    <template #prepend
                                      ><VIcon
                                        :disabled="
                                          getButtonDisable(button, item)
                                        "
                                        :icon="getButtonIcon(button, item)"
                                        size="20"
                                        class="mr-2"
                                    /></template>
                                    <span style="font-size: 0.8125rem">{{
                                      $t(getButtonTitle(button, item))
                                    }}</span>
                                  </VListItem>
                                </template>
                              </VList>
                            </VMenu>
                          </IconBtn>
                        </slot>
                      </div>
                    </div>
                  </VCard>
                </div>
              </template>

              <!-- Totals on the card view -->
              <div v-if="isSumVisible" class="sum-mobile">
                <span class="sum-mobile__label">{{ sumLabel }}</span>
                <div
                  v-for="header in sumHeaders"
                  :key="header.key"
                  class="sum-mobile__row"
                >
                  <span>{{ header.title }}</span>
                  <strong>{{ sumText(header) }}</strong>
                </div>
                <div v-if="$slots['sum-actions']" class="sum-mobile__row">
                  <slot name="sum-actions" />
                </div>
              </div>
            </div>

            <div
              v-else
              class="table-wrapper d-flex flex-row w-100 table-scroll-wrapper"
              :class="[isFullHeight ? 'h-100' : '']"
            >
              <VDataTableVirtual
                v-bind="{
                  ...$attrs,
                  class: null,
                  label: undefined,
                  id: elementId,
                }"
                :fixed-header="isFullHeight"
                :headers="visibleHeaders"
                :items="dataItems?.data || []"
                :items-per-page="options?.limit"
                :items-length="dataItems?.total || 0"
                :page="options?.page"
                :sort-by="options.sortBy"
                :sort-desc="options.sortDesc"
                :options="options"
                :hide-default-header="hideHeader"
                :hide-default-footer="hideFooter"
                :loading="internalLoading"
                :hover="isHover"
                :disable-sort="disableSort"
                class="text-no-wrap no-scroll-table single-direction-scroll striped-table custom-datatable"
                :class="[
                  isFixedColumnRight && mdAndUp
                    ? 'sticky-action-table striped-table'
                    : '',
                  disableSelect ? 'select-disabled-table' : '',
                ]"
                :height="isFullHeight ? '600' : ''"
                :header-props="{
                  style:
                    'background-color: #F8F8F8; border-bottom: 1px solid #ddd; height: 40px !important; padding: 12px 8px; vertical-align: middle;',
                }"
                virtual-rows
                :ripple="false"
                :item-height="48"
                :bench="10"
                :show-expand="showExpand"
                v-model:expanded="expandedRows"
                :item-value="itemValue"
                :show-select="showSelect"
                :model-value="modelValue"
                @update:model-value="emit('update:modelValue', $event)"
                @click:row="onRowClick"
              >
                <template #no-data
                  ><slot name="no-data"
                    ><div class="no-data-container text-center py-4">
                      <VIcon
                        icon="tabler-info-circle"
                        size="48"
                        color="warning"
                        class="mb-2"
                      />
                      <p class="text-medium-emphasis">
                        {{ $t("No Records Found") }}
                      </p>
                    </div></slot
                  ></template
                >
                <template
                  v-for="(slotName, index) in forwardedSlotNames"
                  :key="index"
                  v-slot:[slotName]="slotProps"
                  ><slot :name="slotName" v-bind="slotProps"></slot
                ></template>
                <template #item.actions="{ item }">
                  <!-- Optional page buttons shown before the row actions
                       (e.g. Save / Cancel while a row is edited inline) -->
                  <slot name="actions-prepend" :item="item" />
                  <template
                    v-for="(button, index) in actionButtons"
                    :key="index"
                  >
                    <VBtn
                      rounded
                      :icon="getButtonIcon(button, item)"
                      size="small"
                      variant="text"
                      :color="getButtonColor(button, item)"
                      :disabled="getButtonDisable(button, item)"
                      :aria-label="$t(getButtonTitle(button, item))"
                      v-if="
                        button.btn &&
                        button.show &&
                        (!button.permission || checkPermission(button, item)) &&
                        (!button.condition || button.condition(item))
                      "
                      @click="() => button.action(item)"
                    >
                      <VIcon :icon="getButtonIcon(button, item)" /><VTooltip
                        open-delay="500"
                        location="top"
                        activator="parent"
                        ><span>{{
                          $t(getButtonTitle(button, item))
                        }}</span></VTooltip
                      >
                    </VBtn>
                  </template>
                  <IconBtn
                    rounded
                    v-if="
                      isMoreAction &&
                      (!isMoreActionCondition || isMoreActionCondition(item))
                    "
                    size="small"
                    :aria-label="$t('More actions')"
                  >
                    <VIcon
                      icon="tabler-dots-vertical"
                      color="grey"
                      size="small"
                    /><VMenu activator="parent"
                      ><VList
                        ><template
                          v-for="(button, index) in actionButtons"
                          :key="index"
                          ><VListItem
                            v-if="
                              !button.btn &&
                              button.show &&
                              (!button.permission ||
                                checkPermission(button, item)) &&
                              (!button.condition || button.condition(item))
                            "
                            @click="() => button.action(item)"
                            :class="[
                              getButtonBackgroundClass(button, item),
                              `text-${getButtonColor(button, item)}`,
                            ]"
                            style="
                              display: flex;
                              align-items: center;
                              min-height: 20px;
                            "
                            ><template #prepend
                              ><VIcon
                                :disabled="getButtonDisable(button, item)"
                                :icon="getButtonIcon(button, item)"
                                style="margin-right: 1px"
                                size="20" /></template
                            ><span style="font-size: 13px">{{
                              $t(getButtonTitle(button, item))
                            }}</span></VListItem
                          ></template
                        ></VList
                      ></VMenu
                    >
                  </IconBtn>
                </template>
                <!-- Totals row: one cell per rendered column, so it always
                     lines up (hidden columns, select / expand columns) -->
                <template
                  v-if="isSumVisible && !$slots.tfoot"
                  #tfoot="{ columns }"
                >
                  <tfoot class="sum-foot">
                    <tr>
                      <td
                        v-for="(column, i) in columns"
                        :key="column.key ?? i"
                        :class="[
                          `text-${column.align || 'start'}`,
                          {
                            'sum-foot__value': headerOf(column)?.sum,
                            'sum-foot__fixed': column.fixed,
                            'v-data-table-column--last-fixed': column.lastFixed,
                          },
                        ]"
                        :style="
                          column.fixed
                            ? { left: `${column.fixedOffset || 0}px` }
                            : undefined
                        "
                      >
                        <template v-if="headerOf(column)?.sum">{{
                          sumText(headerOf(column))
                        }}</template>
                        <span
                          v-else-if="i === sumLabelIndex(columns)"
                          class="sum-foot__label"
                          ><VIcon icon="tabler-sum" size="14" />{{
                            sumLabel
                          }}</span
                        >
                        <!-- the action column's cell: free for the page
                             (e.g. a balance) through the sum-actions slot -->
                        <slot
                          v-else-if="column.key === 'actions'"
                          name="sum-actions"
                        />
                      </td>
                    </tr>
                  </tfoot>
                </template>
              </VDataTableVirtual>

              <VMenu
                v-model="rowMenu"
                :target="rowMenuTarget"
                location="bottom start"
                :close-on-content-click="true"
              >
                <VList v-if="rowMenuItem">
                  <VListItem
                    v-for="(button, index) in getRowActions(rowMenuItem)"
                    :key="index"
                    @click="() => button.action(rowMenuItem)"
                    :class="[
                      getButtonBackgroundClass(button, rowMenuItem),
                      `text-${getButtonColor(button, rowMenuItem)}`,
                    ]"
                    style="display: flex; align-items: center; min-height: 20px"
                  >
                    <template #prepend
                      ><VIcon
                        :icon="getButtonIcon(button, rowMenuItem)"
                        style="margin-right: 1px"
                        size="20"
                    /></template>
                    <span style="font-size: 13px">{{
                      $t(getButtonTitle(button, rowMenuItem))
                    }}</span>
                  </VListItem>
                </VList>
              </VMenu>
            </div>

            <table ref="dataTableRef" style="display: none">
              <thead>
                <tr>
                  <th v-for="header in exportHeaders" :key="header.key">
                    {{ header.title }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in dataItems.data" :key="item.id || item.code">
                  <td v-for="header in exportHeaders" :key="header.key">
                    {{
                      header.exportValue
                        ? header.exportValue(item)
                        : header.value && typeof header.value === "function"
                          ? header.value(item)
                          : getValue(item, header.key)
                    }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="app-card-filter-panel" v-if="!isHeader">
            <VExpandTransition
              ><div v-if="renderFilterContent && isFilterOpen">
                <VDivider />
                <div class="pa-2 pt-3"><slot name="filter"></slot></div></div
            ></VExpandTransition>
          </div>
          <VForm class="chat-log-message-form sticky-footer"
            ><div v-if="isPagination">
              <VDivider /><VCol style="background-color: white" class="pa-2"
                ><VCardText style="padding: 0"
                  ><!-- with a `footer-actions` slot the bar turns compact: the
                       tool icons on the left and the page's own actions on the
                       right, all in one row -->
                  <div
                    class="d-flex gap-y-2 align-center"
                    :class="
                      $slots['footer-actions']
                        ? 'flex-nowrap justify-space-between'
                        : 'flex-wrap justify-center justify-sm-space-between'
                    "
                  >
                    <div class="d-flex flex-row align-center">
                      <AppSelect
                        v-if="!$slots['footer-actions']"
                        density="compact"
                        v-model="options.limit"
                        :items="[
                          { title: $t('5'), value: 5 },
                          { title: $t('10'), value: 10 },
                          { title: $t('15'), value: 15 },
                          { title: $t('25'), value: 25 },
                          { title: $t('50'), value: 50 },
                          { title: $t('100'), value: 100 },
                          { title: $t('All'), value: -1 },
                        ]"
                        hide-details
                        style="
                          max-inline-size: 8rem;
                          min-inline-size: 5rem;
                          margin-right: 5px;
                        "
                      />

                      <IconBtn
                        rounded
                        size="30"
                        class="mr-1"
                        v-if="isExcel && !isHeader"
                        :aria-label="$t('Excel')"
                        @click.prevent="exportToExcel"
                        ><VIcon icon="tabler-file-spreadsheet" size="20"
                      /></IconBtn>

                      <!-- Totals row on / off -->
                      <IconBtn
                        rounded
                        size="30"
                        class="mr-1"
                        v-if="hasSum && !isHeader"
                        :aria-label="$t(sumVisible ?'Hide totals' : 'Show totals')"
                        @click.prevent="toggleSum"
                        ><VIcon
                          :icon="sumVisible ? 'tabler-layout-bottombar-filled' : 'tabler-layout-bottombar-inactive'"
                          size="20"
                          :color="sumVisible ?'primary' : undefined" /><VTooltip
                          activator="parent"
                          location="top"
                          open-delay="400"
                          >{{ $t(sumVisible ?"Hide totals" : "Show totals") }}</VTooltip
                        ></IconBtn
                      >

                      <VMenu
                        v-if="isToggleColumn && !isHeader"
                        v-model="menu"
                        :close-on-content-click="false"
                        scroll-strategy="none"
                        eager
                        :content-class="'filter-menu-content'"
                        location="left"
                      >
                        <template #activator="{ props }"
                          ><IconBtn
                            rounded
                            v-bind="props"
                            size="30"
                            class="mr-2"
                            :aria-label="$t('Toggle Columns')"
                            ><VIcon
                              icon="tabler-columns-3"
                              size="20" /></IconBtn
                        ></template>
                        <VCard min-width="300" class="pb-4">
                          <VList
                            ><VListItem :title="$t('Toggle Columns')"
                              ><template v-slot:prepend
                                ><VIcon
                                  icon="tabler-columns-3"
                                ></VIcon></template></VListItem
                          ></VList>
                          <VDivider />
                          <VList
                            v-for="(header, index) in headers"
                            :key="header?.key"
                            class="pt-0 pb-0"
                            ><VListItem
                              v-if="header?.key !== 'actions'"
                              style="padding-bottom: 0 !important"
                              ><VSwitch
                                v-model="header.visible"
                                :label="header.title"
                                color="primary"
                                hide-details /></VListItem
                          ></VList>
                        </VCard>
                      </VMenu>

                      <IconBtn
                        rounded
                        @click.prevent="toggleFilters"
                        size="30"
                        class="mr-2"
                        v-if="isFilter && !isHeader"
                        :aria-label="$t('Filter')"
                      >
                        <VTooltip
                          location="top"
                          transition="scale-transition"
                          activator="parent"
                        >
                          {{ $t("Filter") }}
                        </VTooltip>
                        <VIcon
                          :icon="
                            showFilters[0] == 0
                              ? 'tabler-filter-down'
                              : 'tabler-filter-up'
                          "
                          size="20"
                        />
                      </IconBtn>
                    </div>
                    <div
                      v-if="$slots['footer-actions']"
                      class="d-flex align-center ga-2 min-w-0"
                    >
                      <slot name="footer-actions"></slot>
                    </div>
                    <div v-else>
                      <VChip variant="outlined"
                        ><span style="font-size: 13px"
                          >{{ dataItems?.meta?.from || 0 }}-{{
                            dataItems?.meta?.to || 0
                          }}
                          of {{ dataItems?.meta?.total || 0 }}</span
                        ></VChip
                      >
                    </div>
                    <!-- nothing to page through with a single page (or "All",
                         limit -1), and on mobile it wraps onto its own row -->
                    <VPagination
                      v-if="
                        calculateLength(
                          dataItems?.meta?.total || 0,
                          options.limit,
                        ) > 1
                      "
                      v-model="options.page"
                      active-color="primary"
                      size="small"
                      :total-visible="$vuetify.display.smAndDown ? 4 : 7"
                      :length="
                        calculateLength(
                          dataItems?.meta?.total || 0,
                          options.limit,
                        )
                      "
                      class="bootstrap-style-pagination"
                      @click.prevent
                    /></div></VCardText
              ></VCol>
            </div>
            <slot name="footer"></slot
          ></VForm>
        </div>
      </VMain>
    </VLayout>
  </div>
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core/scss/base/mixins";
@use "@layouts/styles/mixins" as layoutsMixins;
$app-card-header-height: 76px;
%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $app-card-header-height;
  padding-inline: 1.5rem;
}
.app-card-layout {
  border-radius: vuetify.$card-border-radius;
  @include mixins.elevation(vuetify.$card-elevation);
  $sel-app-card-layout: &;
  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-app-card-layout);
    }
  }
  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }
}
</style>

<style scoped>
.mobile-card-container {
  overflow-y: auto;
}

/* A long value wraps inside the card instead of pushing the arrow out */
.mobile-card-side {
  flex: 0 1 auto;
  max-inline-size: 50%;
  min-inline-size: 0;
}

.mobile-card-side__value,
.mobile-card-long-text {
  min-inline-size: 0;
  overflow-wrap: anywhere;
}

/* Stays in view and centred when a wide table scrolls sideways */
.no-data-container {
  inline-size: min(100%, calc(100vw - 3rem));
  inset-inline-start: 0;
  position: sticky;
}

.mobile-card-dot {
  position: absolute;
  z-index: 1;
  border-radius: 50%;
  block-size: 8px;
  inline-size: 8px;
  inset-block-start: 8px;
  inset-inline-start: 8px;
}
/* fixed row above the card list; the inline padding (12px list padding +
   1px card border + 16px card padding) lines its checkbox up with the
   checkboxes on the cards below */
.mobile-select-all {
  flex-shrink: 0;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  min-block-size: 48px;
  padding-block: 6px;
  padding-inline: 29px 16px;
}
.mobile-select-all__label {
  font-size: 0.875rem;
  font-weight: 500;
}
/* key facts on the face of the mobile card: "label ..... value" rows in a
   soft block, values right-aligned to line up with the summary amount */
.mobile-card-front {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 4px 16px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  margin-block: 0 12px;
  margin-inline: 16px;
  padding-block: 8px;
  padding-inline: 12px;
}
.mobile-card-front__cell {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-block-size: 24px;
  min-inline-size: 0;
}
.mobile-card-front__label {
  flex-shrink: 0;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.8125rem;
}
.mobile-card-front__value {
  overflow: hidden;
  font-size: 0.8125rem;
  font-weight: 600;
  text-align: end;
  text-overflow: ellipsis;
  white-space: nowrap;
}
[class^="filter-menu-content-"] {
  overflow: visible;
}
.custom-datatable :deep(.v-table__wrapper) {
  overflow-x: auto;
  overflow-y: auto;
}
.custom-datatable :deep(.v-table__wrapper:hover) {
  overflow-y: auto;
}
.sticky-footer {
  position: sticky;
  bottom: 0;
  background-color: white;
  z-index: 10;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
:deep(.filter-menu-content) {
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
  border-radius: 12px;
}
.scrollable-actions {
  overflow-x: auto;
  white-space: nowrap;
}
.scrollable-actions::-webkit-scrollbar {
  height: 6px;
}
.table-scroll-wrapper {
  scroll-snap-type: y mandatory;
}
.list-tab-height {
  height: calc(100dvh - 147px);
  display: flex;
  flex-direction: column;
}
.list-height {
  height: calc(100dvh - 85px) !important;
}
@media (max-width: 768px) {
  .list-tab-height {
    height: calc(100dvh - 147px);
    position: relative;
  }
  .h-100 {
    height: 100% !important;
    min-height: 0;
  }
}
/* Pagination as one button group: a single bordered strip, dividers between
   the buttons, only the outer ends rounded */
.bootstrap-style-pagination :deep(.v-pagination__list) {
  display: inline-flex;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  gap: 0;
}
.bootstrap-style-pagination :deep(.v-pagination__list > li) {
  margin: 0;
}
.bootstrap-style-pagination :deep(.v-pagination__list > li + li) {
  border-inline-start: 1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}
.bootstrap-style-pagination :deep(.v-btn) {
  border-radius: 0;
  box-shadow: none !important;
  block-size: 32px;
  min-inline-size: 34px;
  inline-size: auto;
  padding-inline: 10px;
  /* plain white cells; the theme's grey tonal fill is dropped */
  background-color: transparent !important;
}
.bootstrap-style-pagination :deep(.v-btn .v-btn__underlay) {
  opacity: 0 !important;
}
/* current page: solid primary cell (the theme's own active style is
   overridden here, so this needs !important too) */
.bootstrap-style-pagination :deep(.v-pagination__item--is-active .v-btn) {
  background-color: rgb(var(--v-theme-primary)) !important;
  font-weight: 600;
}
.bootstrap-style-pagination
  :deep(.v-pagination__item--is-active .v-btn .v-btn__content) {
  color: rgb(var(--v-theme-on-primary)) !important;
}

.striped-table tbody tr:nth-of-type(odd) {
  background-color: rgba(0, 0, 0, 0.05);
}
.sticky-action-table :deep(.v-data-table) {
  position: relative;
  isolation: isolate;
}
.sticky-action-table :deep(.v-data-table-header) {
  position: sticky;
  top: 0;
  z-index: 1000;
  background-color: #f8f8f8;
}
.sticky-action-table :deep(th:last-child) {
  position: sticky;
  right: 0;
  top: 0;
  z-index: 1200;
  background-color: #f8f8f8;
  border-left: 1px solid #e0dfe1;
}
.sticky-action-table :deep(td:last-child) {
  position: sticky;
  right: 0;
  z-index: 0;
  background-color: #ffffff;
  border-left: 1px solid #e0dfe1;
}
.select-disabled-table :deep(.v-selection-control) {
  pointer-events: none;
  opacity: 0.38;
}
</style>

<style>
/* Totals row (AppCardTable `sum` headers): pinned to the bottom of the
   scrolling table. Not scoped: the cells are rendered inside VDataTable. */
.custom-datatable tfoot.sum-foot td {
  position: sticky;
  bottom: 0;
  z-index: 2;
  height: 46px;
  padding: 0 16px;
  white-space: nowrap;
  border-top: 2px solid rgba(var(--v-theme-primary), 0.45);
  border-bottom: 0;
  /* neutral tint like the table header, solid so rows don't show through */
  background:
    linear-gradient(rgba(var(--v-theme-on-surface), 0.035), rgba(var(--v-theme-on-surface), 0.035)),
    rgb(var(--v-theme-surface));
  box-shadow: 0 -6px 10px -8px rgba(var(--v-theme-on-surface), 0.25);
}

/* "Total" / "Page total" chip */
.custom-datatable tfoot.sum-foot .sum-foot__label {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px 3px 8px;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 600;
  line-height: 1.4;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.12);
}

/* Pinned columns stay pinned in the totals row too: the left ones keep
   their offset, the action column (last) stays on the right */
.custom-datatable tfoot.sum-foot td.sum-foot__fixed {
  z-index: 3;
}

.custom-datatable.sticky-action-table tfoot.sum-foot td:last-child {
  right: 0;
  z-index: 3 !important;
  border-left: 1px solid #e0dfe1;
  background:
    linear-gradient(rgba(var(--v-theme-on-surface), 0.035), rgba(var(--v-theme-on-surface), 0.035)),
    rgb(var(--v-theme-surface)) !important;
}

.custom-datatable tfoot.sum-foot td.sum-foot__value {
  font-family: inter, kantumruy, sans-serif;
  font-size: 14px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: rgba(var(--v-theme-on-surface), 0.92);
}

.sum-mobile {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.07);
}

.sum-mobile__label {
  font-size: 12px;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

.sum-mobile__row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  font-size: 13.5px;
}

.sum-mobile__row strong {
  font-family: inter, kantumruy, sans-serif;
  font-variant-numeric: tabular-nums;
}
</style>
