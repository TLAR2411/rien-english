import { useI18n } from "vue-i18n";

// The name of a bilingual record (name_kh / name_en) in the current language
export function useLocalName() {
  const { locale } = useI18n();

  return (item) =>
    (locale.value === "km" ? item?.name_kh : item?.name_en || item?.name_kh) ?? "";
}
