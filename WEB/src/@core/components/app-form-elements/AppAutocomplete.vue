<script setup>
import {
  computed,
  ref,
  watch,
  useId,
  onMounted,
  onBeforeUnmount,
  nextTick,
} from "vue";
import { api } from "@/utils/api";
import { useSettingStore } from "@/stores/settingStore";

defineOptions({
  name: "AppSelect",
  inheritAttrs: false,
});

const props = defineProps({
  items: { type: Array, default: () => [] },
  itemTitle: { type: [String, Function, Array], default: "title" },
  itemValue: { type: String, default: "value" },
  label: String,
  id: String,
  multiple: Boolean,
  class: String,
  serverSide: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  debounce: { type: Number, default: 300 },
  apiUrl: { type: String, default: null },
  resetOnBranchChange: { type: Boolean, default: false },
  externalTrigger: Boolean,
});

const emit = defineEmits(["search", "update:menu", "update:items"]);
const model = defineModel();

const settingStore = useSettingStore();
const selectRef = ref(null);
const internalItems = ref([...props.items]);
const isFetching = ref(false);
const isMenuOpen = ref(false);
const searchQuery = ref("");
const debouncedQuery = ref("");
const searchInputRef = ref(null);
let searchTimeout = null;
const selectedItemsCache = ref([]);

const generatedId = useId();
const elementId = computed(() =>
  props.id ? `app-select-${props.id}` : generatedId,
);

const uniqueMenuClass = computed(() => `app-select-menu-${elementId.value}`);

// --- Helper: Robust Value Extraction ---
const getValue = (item) => {
  if (item === null || item === undefined) return null;
  const val = typeof item === "object" ? item[props.itemValue] : item;
  return val !== null && val !== undefined ? String(val) : null;
};

// --- Search Logic ---
const resetSearch = () => {
  searchQuery.value = "";
  debouncedQuery.value = "";
  clearTimeout(searchTimeout);
};

const searchItems = async (query) => {
  if (!props.apiUrl) return;
  isFetching.value = true;
  const q = query ? String(query).trim() : "";

  try {
    const payload = { filter: { search: q } };
    const response = await api.get(props.apiUrl, {params:payload});

    const validData =
      response.data?.status && Array.isArray(response.data.data)
        ? response.data.data
        : [];

    internalItems.value = validData;
    emit("update:items", internalItems.value);
  } catch (error) {
    // Check if the error is a cancellation
    if (error.name === "CanceledError" || error.message === "canceled") {
      return; // Do nothing, a newer request is on the way
    }
    console.error("API search error:", error);
    internalItems.value = [];
  } finally {
    // Only stop loading if we aren't expecting another immediate update
    isFetching.value = false;
  }
};

// --- Watchers ---
watch(searchQuery, (val, oldVal) => {
  clearTimeout(searchTimeout);
  if (oldVal && !val) {
    debouncedQuery.value = "";
    if (props.serverSide) {
      props.apiUrl ? searchItems("") : emit("search", "");
    }
    return;
  }
  searchTimeout = setTimeout(() => {
    debouncedQuery.value = val;
    if (props.serverSide) {
      props.apiUrl ? searchItems(val) : emit("search", val);
    }
  }, props.debounce);
});

watch(
  isMenuOpen,
  (val, oldVal) => {
    if (val === true && oldVal === false) {
      if (props.serverSide && props.apiUrl) searchItems("");
      nextTick(() => searchInputRef.value?.focus());
    }
    if (oldVal === true && val === false) {
      // resetSearch();
    }
    emit("update:menu", val);
  },
  { flush: "post" },
);

watch(
  () => props.items,
  (newItems) => {
    if (!props.apiUrl) internalItems.value = [...newItems];
  },
  { deep: true, immediate: true },
);

watch(
  () => settingStore.branch_id,
  () => {
    if (!props.resetOnBranchChange) return;
    model.value = props.multiple ? [] : null;
    resetSearch();
    if (props.serverSide && props.apiUrl) searchItems("");
  },
);

// --- Selected Cache ---
watch(
  () => model.value,
  (newVal) => {
    if (!newVal || (Array.isArray(newVal) && newVal.length === 0)) {
      selectedItemsCache.value = [];
      return;
    }
    const items = internalItems.value || [];
    const values = Array.isArray(newVal) ? newVal : [newVal];
    const strValues = values.map((v) => String(v));

    const newlySelected = items.filter((item) =>
      strValues.includes(getValue(item)),
    );

    const updatedCache = [];
    selectedItemsCache.value.forEach((cached) => {
      if (strValues.includes(getValue(cached))) updatedCache.push(cached);
    });
    newlySelected.forEach((item) => {
      const val = getValue(item);
      if (!updatedCache.some((c) => getValue(c) === val))
        updatedCache.push(item);
    });

    selectedItemsCache.value = updatedCache;
  },
  { immediate: true },
);
const onGlobalKeyDown = (e) => {
  if (!isMenuOpen.value) return;

  // Ignore modifier keys
  if (e.ctrlKey || e.metaKey || e.altKey) return;

  // Ignore navigation keys
  if (["ArrowUp", "ArrowDown", "Enter", "Escape", "Tab"].includes(e.key))
    return;

  // Only printable characters
  if (e.key.length !== 1) return;

  const input = searchInputRef.value?.$el?.querySelector("input");
  if (!input) return;

  // --- FIX START ---
  // If the input is already focused, let the browser handle the input naturally.
  // This allows spaces and normal typing without preventDefault interference.
  if (document.activeElement === input) return;

  // Specifically allow the Space key to behave normally if we are inside the input
  // (though the check above usually covers this)
  if (e.key === " " && document.activeElement === input) return;
  // --- FIX END ---

  e.preventDefault();

  input.focus();
  searchQuery.value += e.key;
};

const handleClickOutside = (event) => {
  // Check if the click was outside the entire AppSelect component
  if (
    isMenuOpen.value &&
    selectRef.value &&
    !selectRef.value.contains(event.target)
  ) {
    // 1. Close the menu
    isMenuOpen.value = false;

    // 2. Remove the cursor (blur)
    const input = searchInputRef.value?.$el?.querySelector("input");
    if (input) {
      input.blur();
    }
  }
};
const isMobile = window.matchMedia("(pointer: coarse)").matches;

onMounted(() => {
  if (props.serverSide && props.apiUrl) searchItems("");
  if (!isMobile) {
    window.addEventListener("keydown", onGlobalKeyDown);
  }
  // window.addEventListener("mousedown", handleClickOutside);
});

onBeforeUnmount(() => {
  clearTimeout(searchTimeout);
  if (!isMobile) {
    window.removeEventListener("keydown", onGlobalKeyDown);
  }
  // window.removeEventListener("mousedown", handleClickOutside);
});

// --- Logic ---

const filteredItems = computed(() => {
  if (props.serverSide) return internalItems.value || [];
  const query = debouncedQuery.value.toLowerCase();
  if (!query) return internalItems.value || [];

  return (internalItems.value || []).filter((item) => {
    if (typeof item === "object") {
      return Object.values(item).some((v) =>
        String(v).toLowerCase().includes(query),
      );
    }
    return String(item).toLowerCase().includes(query);
  });
});

// These are ONLY the items that match the search
const strictMenuItems = computed(() => {
  return props.serverSide ? internalItems.value : filteredItems.value;
});

// Create a Set of valid IDs for O(1) lookup
const visibleIds = computed(() => {
  const ids = new Set();
  if (strictMenuItems.value) {
    strictMenuItems.value.forEach((i) => {
      const val = getValue(i);
      if (val !== null) ids.add(val);
    });
  }
  return ids;
});

// The items passed to VSelect must include EVERYTHING (menu + selected)
// so the input label works. We will hide the non-matching ones in the template.
const itemsForSelect = computed(() => {
  const currentMenu = strictMenuItems.value || [];
  const currentMenuIds = new Set(currentMenu.map((i) => getValue(i)));

  // Add hidden selected items so the label renders correctly
  const hiddenSelected = selectedItemsCache.value.filter(
    (s) => !currentMenuIds.has(getValue(s)),
  );

  return [...currentMenu, ...hiddenSelected];
});

const showNoData = computed(() => {
  if (props.loading || isFetching.value) return false;
  return !strictMenuItems.value || strictMenuItems.value.length === 0;
});

// --- Strict Visibility Check ---
const shouldShowItem = (itemRaw) => {
  // If no search query exists, show everything
  if (!debouncedQuery.value && !searchQuery.value) return true;

  // Strict check: Does this item exist in our filtered list?
  return visibleIds.value.has(getValue(itemRaw));
};

// --- Event Handlers ---
const onSearchKeyDown = (e) => {
  if (e.key === " ") {
    e.stopPropagation();
    return; // Return early so we don't process other logic
  }

  if (isMobile) return;

  if (e.key === "Tab") {
    isMenuOpen.value = false;
    return;
  }
  if (e.key === "ArrowDown") {
    e.preventDefault();
    setTimeout(() => {
      document.querySelector(`.${uniqueMenuClass.value} .v-list-item`)?.focus();
    }, 0);
    return;
  }
  if (e.key === "Enter") {
    e.preventDefault();
    if (strictMenuItems.value?.length > 0) {
      const firstItem = strictMenuItems.value[0];
      const val =
        typeof firstItem === "object" ? firstItem[props.itemValue] : firstItem;

      if (props.multiple) {
        const current = Array.isArray(model.value) ? [...model.value] : [];
        if (!current.includes(val)) model.value = [...current, val];
      } else {
        model.value = val;
        isMenuOpen.value = false;
      }
    }
    return;
  }
  if (e.key === "Escape") {
    e.preventDefault();
    isMenuOpen.value = false;
  }
};

// Inside onSearchInputClear
const onSearchInputClear = () => {
  searchQuery.value = "";
  debouncedQuery.value = "";
  clearTimeout(searchTimeout);

  if (props.serverSide && props.apiUrl) {
    searchItems("");
  } else {
    // If local, emit the full original list
    emit("update:items", props.items);
  }
  nextTick(() => searchInputRef.value?.focus());
};

const menuActivator = computed({
  get: () => isMenuOpen.value,
  set: (val) => {
    if (val && !isMenuOpen.value) {
      nextTick(() => {
        searchQuery.value = "";
        debouncedQuery.value = "";
      });
    }
    isMenuOpen.value = val;
  },
});

watch(
  () => props.externalTrigger,
  (newVal) => {
    if (newVal === true) {
      // Close THIS select because something else just opened
      isMenuOpen.value = false;
    }
  },
);
</script>

<template>
  <div ref="selectRef" class="app-select flex-grow-1 pa-0" :class="props.class">
    <VLabel
      v-if="props.label"
      :for="elementId"
      class="mb-1 text-wrap notasans font-size-0-75 pt-2"
      style="line-height: 15px"
      :text="$t(props.label)"
    />

    <VSelect
      v-model="model"
      v-model:menu="menuActivator"
      :items="itemsForSelect"
      :item-title="itemTitle"
      :item-value="itemValue"
      :multiple="props.multiple"
      :id="elementId"
      variant="outlined"
      :virtual-scroll="false"
      :menu-props="{
        contentClass: `${uniqueMenuClass} app-select-menu`,
        maxHeight: 400,
        closeOnContentClick: !props.multiple,
      }"
      v-bind="$attrs"
      clear-icon="tabler-x"
    >
      <template #prepend-item>
        <div class="search-sticky-container">
          <div class="select-search-wrapper">
            <VTextField
              type="text"
              ref="searchInputRef"
              v-model="searchQuery"
              placeholder="ស្វែងរក..."
              hide-details
              autocomplete="off"
              class="select-search-input"
              @keydown="onSearchKeyDown"
              @click.stop
              clearable
              :loading="props.loading || isFetching"
              @click:clear="onSearchInputClear"
              clear-icon="tabler-x"
            >
              <template #prepend-inner>
                <VIcon icon="tabler-search" size="20" class="mr-2" />
              </template>
            </VTextField>
          </div>
          <div v-if="showNoData" class="pa-4 text-center text-medium-emphasis">
            {{ $t("$vuetify.noDataText") }}
          </div>
        </div>
      </template>

      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot
          :name="name"
          v-bind="slotProps"
          v-if="name != 'item' || (name == 'item' && !showNoData)"
        />
      </template>

      <template #item="{ item, props: itemProps }">
        <template v-if="shouldShowItem(item.raw)">
          <slot
            v-if="$slots.item"
            name="item"
            :item="item"
            :props="itemProps"
          />

          <VListItem
            v-else
            v-bind="itemProps"
            :title="item.title ?? item.value ?? ''"
          />
        </template>
      </template>

      <template #no-data>
        <span></span>
      </template>
    </VSelect>
  </div>
</template>

<style scoped>
.search-sticky-container {
  position: sticky;
  top: 0;
  z-index: 10;
  background: rgb(var(--v-theme-surface));
}

.select-search-wrapper {
  padding: 8px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.select-search-input :deep(.v-field__outline) {
  display: none;
}

.select-search-input :deep(.v-field__input) {
  min-height: 32px;
  padding: 4px 0;
  font-size: 14px;
}

.select-search-input :deep(input::placeholder) {
  color: #9e9e9e;
  opacity: 1;
}
</style>

<style>
.v-overlay__content.app-select-menu .v-list {
  padding-top: 0 !important;
  padding-bottom: 8px !important;
}

/* iOS Safari zooms the page when a focused input is under 16px.
   iOS only (-webkit-touch-callout), so desktop keeps the 14px look.
   Plain selectors: :deep() does not work in a non-scoped block, and in
   Vuetify 3 the <input> itself carries .v-field__input. */
@supports (-webkit-touch-callout: none) {
  .select-search-input input,
  .app-select input {
    font-size: 16px !important;
  }
}
</style>
