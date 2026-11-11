<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { app } from "@/utils/app.js";
import AppDialog from "@/components/AppDialog.vue";
import AppDateTimePicker from "@core/components/app-form-elements/AppDateTimePicker.vue";
import { requiredValidator } from "@/@core/utils/validators";

/**
 * Create / edit a branch: details, address and the app check-in location.
 * Saving is done by the list (onCreate / onUpdate events, same as before).
 */
const props = defineProps({
  itemData: { type: Object, default: () => ({}) },
  isDialogVisible: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isDialogVisible", "onCreate", "onUpdate"]);

const { t } = useI18n();

const emptyForm = () => ({
  name_kh: "",
  name_en: "",
  abbr: "",
  contact: "",
  house_no: "",
  street: "",
  village_code: null,
  commune_id: null,
  district_id: null,
  province_id: null,
  region: "",
  start_date: null,
  latitude: null,
  longitude: null,
  check_in_radius_m: 150,
  // Must app check-ins be inside the radius? (off = anywhere, with a reason)
  require_check_in_area: true,
});

const form = ref({ ...emptyForm(), ...props.itemData });
const isEdit = computed(() => !!form.value.id);

// Re-fill whenever the list hands over another branch (or an empty one)
watch(
  () => props.itemData,
  (data) => {
    form.value = { ...emptyForm(), ...data };
    fillAddressFromVillage();
  },
);

// ---------- Address (province -> district -> commune -> village) ----------
const provinces = computed(() => app().provinces || []);
const districts = computed(() =>
  form.value.province_id
    ? app().districts.filter(
        (v) => v.province_code === Number(form.value.province_id),
      )
    : [],
);
const communes = computed(() =>
  form.value.district_id
    ? app().communes.filter(
        (v) => v.district_code === Number(form.value.district_id),
      )
    : [],
);
const villages = computed(() =>
  form.value.commune_id
    ? app().villages.filter(
        (v) => v.commune_code === Number(form.value.commune_id),
      )
    : [],
);

// Picking a higher level clears the levels below it
const onProvince = () => {
  form.value.district_id = null;
  form.value.commune_id = null;
  form.value.village_code = null;
};
const onDistrict = () => {
  form.value.commune_id = null;
  form.value.village_code = null;
};
const onCommune = () => {
  form.value.village_code = null;
};

// Editing: the branch only stores the village, so work out the levels above it
const fillAddressFromVillage = () => {
  const code = Number(form.value.village_code);
  if (!code || form.value.province_id) return;
  const village = app().villages.find((v) => v.code === code);
  const commune =
    village && app().communes.find((v) => v.code === village.commune_code);
  const district =
    commune && app().districts.find((v) => v.code === commune.district_code);
  if (!district) return;
  form.value.commune_id = commune.code;
  form.value.district_id = district.code;
  form.value.province_id = district.province_code;
};
fillAddressFromVillage();

// ---------- Check-in location ----------
const isLocating = ref(false);
const locateError = ref("");

// Fill the branch position from this device (stand in the branch and tap)
const useMyLocation = () => {
  locateError.value = "";
  if (!navigator.geolocation) {
    locateError.value = "This device cannot share its location";
    return;
  }
  isLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.value.latitude = +pos.coords.latitude.toFixed(7);
      form.value.longitude = +pos.coords.longitude.toFixed(7);
      isLocating.value = false;
    },
    (err) => {
      locateError.value =
        err.code === err.PERMISSION_DENIED
          ? "Location permission was denied. Please allow location for this site."
          : "Could not get your location. Please try again.";
      isLocating.value = false;
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
  );
};

const hasLocation = computed(
  () =>
    form.value.latitude !== null &&
    form.value.latitude !== "" &&
    form.value.longitude !== null &&
    form.value.longitude !== "",
);
const mapUrl = computed(() =>
  hasLocation.value
    ? `https://www.google.com/maps?q=${form.value.latitude},${form.value.longitude}`
    : null,
);
// Small preview of the pin (no API key needed for the embed)
const mapEmbed = computed(() =>
  hasLocation.value
    ? `https://maps.google.com/maps?q=${form.value.latitude},${form.value.longitude}&z=17&output=embed`
    : null,
);

const clearLocation = () => {
  form.value.latitude = null;
  form.value.longitude = null;
};

// ---------- Rules ----------
const abbrRule = (v) =>
  /^[A-Za-z]{2,3}$/.test(v || "") || t("2 or 3 letters, e.g. BTB");
const latRule = (v) =>
  v === null || v === "" || (Number(v) >= -90 && Number(v) <= 90) || "-90 … 90";
const lngRule = (v) =>
  v === null ||
  v === "" ||
  (Number(v) >= -180 && Number(v) <= 180) ||
  "-180 … 180";
const radiusRule = (v) =>
  (Number(v) >= 20 && Number(v) <= 5000) || t("Between 20 and 5000 m");
const bothOrNone = () =>
  (form.value.latitude === null || form.value.latitude === "") ===
    (form.value.longitude === null || form.value.longitude === "") ||
  t("Enter both latitude and longitude");

// ---------- Save ----------
const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid || props.loading) return;

  const data = {
    ...form.value,
    abbr: (form.value.abbr || "").toUpperCase(),
    latitude: hasLocation.value ? Number(form.value.latitude) : null,
    longitude: hasLocation.value ? Number(form.value.longitude) : null,
    check_in_radius_m: Number(form.value.check_in_radius_m) || 150,
    require_check_in_area: form.value.require_check_in_area !== false,
  };
  // The list closes the dialog and reloads on success
  emit(isEdit.value ? "onUpdate" : "onCreate", data, () => {});
};
</script>

<template>
  <AppDialog
    :title="isEdit ? 'Update Branch' : 'Create Branch'"
    :title-icon="isEdit ? 'tabler-building-cog' : 'tabler-building-plus'"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => $emit('update:isDialogVisible', v)"
    :width="860"
    is-footer
    is-submit
    :submit-text="isEdit ? 'Update' : 'Create'"
    :submit-icon="isEdit ? 'tabler-device-floppy' : 'tabler-plus'"
    :submit-color="isEdit ? 'warning' : 'primary'"
    :loading="loading"
    @on-submit="onSubmit"
  >
    <div class="branch-form">
      <!-- Branch -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"
            ><VIcon icon="tabler-building" size="15"
          /></span>
          {{ $t("Branch") }}
        </div>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="form.name_kh"
              placeholder="សាខាបាត់ដំបង"
              :rules="[requiredValidator]"
            >
              <template #label
                >{{ $t("Name Khmer") }}
                <span class="text-error">*</span></template
              >
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="form.name_en"
              placeholder="Battambang Branch"
            >
              <template #label>{{ $t("Name English") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="6" sm="3">
            <AppTextField
              v-model="form.abbr"
              placeholder="BTB"
              maxlength="3"
              class="abbr-field"
              :rules="[abbrRule]"
              @update:model-value="(v) => (form.abbr = (v || '').toUpperCase())"
            >
              <template #label
                >{{ $t("Abbr") }} <span class="text-error">*</span></template
              >
            </AppTextField>
          </VCol>
          <VCol cols="6" sm="3">
            <AppTextField
              v-model="form.region"
              type="number"
              min="1"
              placeholder="1"
            >
              <template #label>{{ $t("Region") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="form.contact"
              placeholder="098 898 988"
              prepend-inner-icon="tabler-phone"
            >
              <template #label>{{ $t("Contact") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="3">
            <AppDateTimePicker
              v-model="form.start_date"
              placeholder="01-01-2026"
            >
              <template #label>{{ $t("Start Date") }}</template>
            </AppDateTimePicker>
          </VCol>
        </VRow>
      </section>

      <!-- Address -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"
            ><VIcon icon="tabler-map-2" size="15"
          /></span>
          {{ $t("Address") }}
        </div>
        <VRow>
          <VCol cols="6" sm="3">
            <AppTextField v-model="form.house_no" placeholder="981">
              <template #label>{{ $t("House No") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="6" sm="3">
            <AppTextField v-model="form.street" placeholder="205">
              <template #label>{{ $t("Street") }}</template>
            </AppTextField>
          </VCol>
          <VCol cols="12" sm="6">
            <AppAutocomplete
              v-model="form.province_id"
              :items="provinces"
              item-value="code"
              item-title="name_kh"
              clearable
              @update:model-value="onProvince"
            >
              <template #label>{{ $t("Provinces") }}</template>
            </AppAutocomplete>
          </VCol>
          <VCol cols="12" sm="4">
            <AppAutocomplete
              v-model="form.district_id"
              :items="districts"
              item-value="code"
              item-title="name_kh"
              :disabled="!form.province_id"
              clearable
              @update:model-value="onDistrict"
            >
              <template #label>{{ $t("Districts") }}</template>
            </AppAutocomplete>
          </VCol>
          <VCol cols="12" sm="4">
            <AppAutocomplete
              v-model="form.commune_id"
              :items="communes"
              item-value="code"
              item-title="name_kh"
              :disabled="!form.district_id"
              clearable
              @update:model-value="onCommune"
            >
              <template #label>{{ $t("Communes") }}</template>
            </AppAutocomplete>
          </VCol>
          <VCol cols="12" sm="4">
            <AppAutocomplete
              v-model="form.village_code"
              :items="villages"
              item-value="code"
              item-title="name_kh"
              :disabled="!form.commune_id"
              clearable
            >
              <template #label>{{ $t("Villages") }}</template>
            </AppAutocomplete>
          </VCol>
        </VRow>
      </section>

      <!-- Check-in location -->
      <section>
        <div class="section-title">
          <span class="section-title__icon"
            ><VIcon icon="tabler-map-pin" size="15"
          /></span>
          {{ $t("Check-in Location") }}
        </div>

        <div class="geo">
          <div class="geo__fields">
            <!-- Required inside the area, or allowed anywhere with a reason -->
            <div
              class="require"
              :class="{ 'require--off': form.require_check_in_area === false }"
            >
              <VSwitch
                :model-value="form.require_check_in_area !== false"
                color="primary"
                density="compact"
                hide-details
                inset
                @update:model-value="(v) => (form.require_check_in_area = v)"
              />
              <div class="d-flex flex-column">
                <span class="require__title">{{
                  $t("Must check in inside the branch area")
                }}</span>
                <span class="require__sub">
                  {{
                    form.require_check_in_area !== false
                      ? $t("Check-in outside the radius is refused")
                      : $t(
                          "Staff can check in anywhere; outside the radius needs a reason and is flagged",
                        )
                  }}
                </span>
              </div>
            </div>
            <VBtn
              block
              variant="tonal"
              prepend-icon="tabler-current-location"
              :loading="isLocating"
              @click="useMyLocation"
            >
              {{ $t("Use my current location") }}
            </VBtn>
            <span v-if="locateError" class="text-caption text-error">{{
              $t(locateError)
            }}</span>

            <VRow>
              <VCol cols="6">
                <AppTextField
                  v-model="form.latitude"
                  type="number"
                  step="0.0000001"
                  placeholder="13.0957000"
                  :rules="[latRule, bothOrNone]"
                >
                  <template #label>{{ $t("Latitude") }}</template>
                </AppTextField>
              </VCol>
              <VCol cols="6">
                <AppTextField
                  v-model="form.longitude"
                  type="number"
                  step="0.0000001"
                  placeholder="103.2022000"
                  :rules="[lngRule, bothOrNone]"
                >
                  <template #label>{{ $t("Longitude") }}</template>
                </AppTextField>
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.check_in_radius_m"
                  type="number"
                  min="20"
                  max="5000"
                  suffix="m"
                  prepend-inner-icon="tabler-radar"
                  :rules="[radiusRule]"
                  :hint="$t('Staff farther than this must give a reason')"
                  persistent-hint
                >
                  <template #label>{{ $t("Check-in Radius") }}</template>
                </AppTextField>
              </VCol>
            </VRow>
          </div>

          <!-- Map preview -->
          <div class="geo__map">
            <template v-if="mapEmbed">
              <iframe
                :src="mapEmbed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                :title="$t('Map')"
              />
              <div class="geo__map-actions">
                <VBtn
                  size="x-small"
                  variant="flat"
                  color="surface"
                  prepend-icon="tabler-external-link"
                  :href="mapUrl"
                  target="_blank"
                  rel="noopener"
                >
                  {{ $t("Open Map") }}
                </VBtn>
                <VBtn
                  size="x-small"
                  variant="flat"
                  color="surface"
                  prepend-icon="tabler-x"
                  @click="clearLocation"
                >
                  {{ $t("Clear") }}
                </VBtn>
              </div>
            </template>
            <div v-else class="geo__empty">
              <VIcon icon="tabler-map-pin-off" size="32" />
              <span>{{ $t("Branch location is not set yet") }}</span>
              <span
                class="text-caption"
                style="font-family: inter, kantumruy, sans-serif !important"
                >{{
                  $t("Stand in the branch and tap Use my current location")
                }}</span
              >
            </div>
          </div>
        </div>
      </section>
    </div>
  </AppDialog>
</template>

<style scoped>
.branch-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Section headings (same style as the other HR dialogs) */
.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-size: 14px;
  font-weight: 500;
}

.section-title::after {
  content: "";
  flex: 1;
  height: 1px;
  background: rgba(var(--v-border-color), var(--v-border-opacity));
}

.section-title__icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 7px;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
}

.abbr-field :deep(input) {
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

/* Check-in location: fields on the left, map on the right */
.geo {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1.25rem;
}

.geo__fields {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.require {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.06);
}

.require--off {
  background: rgba(var(--v-theme-warning), 0.08);
}

.require__title {
  font-size: 13.5px;
  font-weight: 500;
}

.require__sub {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.geo__map {
  position: relative;
  min-height: 230px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.geo__map iframe {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 230px;
  border: 0;
}

.geo__map-actions {
  position: absolute;
  top: 0.5rem;
  inset-inline-end: 0.5rem;
  display: flex;
  gap: 0.375rem;
}

.geo__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  height: 100%;
  min-height: 230px;
  padding: 1rem;
  text-align: center;
  font-size: 13px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  background: rgba(var(--v-theme-on-surface), 0.03);
}

@media (max-width: 599px) {
  .geo {
    grid-template-columns: 1fr;
  }
}
</style>
