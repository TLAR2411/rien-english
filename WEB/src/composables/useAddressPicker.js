import { computed, ref } from "vue";
import { app } from "@/utils/app.js";

// Province -> district -> commune -> village selects. Only the village code is
// stored, so fill(code) works out the levels above it when editing.
export function useAddressPicker() {
  const provinceCode = ref(null);
  const districtCode = ref(null);
  const communeCode = ref(null);
  const villageCode = ref(null);

  const childrenOf = (list, key, code) =>
    code ? (app()[list] || []).filter((v) => v[key] === Number(code)) : [];

  const provinces = computed(() => app().provinces || []);
  const districts = computed(() => childrenOf("districts", "province_code", provinceCode.value));
  const communes = computed(() => childrenOf("communes", "district_code", districtCode.value));
  const villages = computed(() => childrenOf("villages", "commune_code", communeCode.value));

  const onProvince = () => {
    districtCode.value = null;
    communeCode.value = null;
    villageCode.value = null;
  };
  const onDistrict = () => {
    communeCode.value = null;
    villageCode.value = null;
  };
  const onCommune = () => {
    villageCode.value = null;
  };

  const fill = (code) => {
    const village = (app().villages || []).find((v) => v.code === Number(code));
    const commune = village && app().communes.find((v) => v.code === village.commune_code);
    const district = commune && app().districts.find((v) => v.code === commune.district_code);

    villageCode.value = village?.code ?? null;
    communeCode.value = commune?.code ?? null;
    districtCode.value = district?.code ?? null;
    provinceCode.value = district?.province_code ?? null;
  };

  return {
    provinceCode, districtCode, communeCode, villageCode,
    provinces, districts, communes, villages,
    onProvince, onDistrict, onCommune, fill,
  };
}
