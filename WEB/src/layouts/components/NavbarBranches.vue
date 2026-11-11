<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import { auth } from "@/utils/auth";
import { useAuthStore } from "@/stores/authStore";
import { useSettingStore } from "@/stores/settingStore";
import { computed, ref, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";

const branches = ref(auth()?.branches || []);
const filter = ref({
  branch_id: useSettingStore().branch_id,
});
const { t, locale } = useI18n();

// Helper function to format branches
const formatBranches = (branchList) => {
  if (branchList?.length > 1) {
    const all = [
      {
        name_kh: "គ្រប់សាខា",
        name_en: "All Branches",
        abbr: null,
        id: "*",
        province_code: null,
      },
    ];
    return [...all, ...branchList];
  }
  return branchList;
};

const showBranches = computed(() => formatBranches(branches.value));

// Watch for changes in auth().branches
watch(
  () => auth()?.branches,
  (newBranches) => {
    if (newBranches) {
      branches.value = newBranches;
      setBranch();
    }
  },
  { deep: true },
);

onMounted(() => {
  setBranch();
});

const setBranch = () => {
  filter.value.branch_id =
    useSettingStore().branch_id || showBranches?.value[0]?.id || null;

  useSettingStore().setBranchId(filter.value.branch_id);

  const branchAbbr = showBranches.value.find(
    (i) => i.id == filter.value.branch_id,
  )?.abbr;
  const provinceCode = showBranches.value.find(
    (i) => i.id == filter.value.branch_id,
  )?.province_code;

  useSettingStore().setBranchAbbr(branchAbbr);

  useSettingStore().setBranchProvinceCode(provinceCode);
};

const changeBranch = (id) => {
  useSettingStore().setBranchId(id);
  const branchAbbr = showBranches.value.find(
    (i) => i.id == filter.value.branch_id,
  )?.abbr;
  const provinceCode = showBranches.value.find(
    (i) => i.id == id,
  )?.province_code;
  useSettingStore().setBranchAbbr(branchAbbr);
  useSettingStore().setBranchProvinceCode(provinceCode);
};
</script>

<template>
  <!-- Up to 230px wide; shrinks on phones so the navbar icons always fit.
       Hidden with one branch: there is nothing to choose. -->
  <div v-if="branches.length > 1" class="navbar-branches">
    <AppAutocomplete
      class="branch-autocomplete"
      v-model="filter.branch_id"
      :items="showBranches"
      :item-title="
        (item) =>
          `${item[locale === 'km' ? 'name_kh' : 'name_en']}` +
          (item.abbr ? `  (${item.abbr})` : '')
      "
      item-value="id"
      @update:model-value="(value) => changeBranch(value)"
      autocomplete="off"
    />
  </div>
</template>

<style scoped>
.navbar-branches {
  flex: 0 1 230px;
  min-width: 0;
}

.branch-autocomplete {
  width: 100%;
}
</style>
