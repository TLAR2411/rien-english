import { debounce } from "lodash";
import { SKILLS } from "@/constants/learning";
import { getSiteLessons, getSiteLevels } from "@/services/site";

/**
 * A lesson list with skill / level / search filters and pages — shared by the
 * public lesson list and the student system's lesson list.
 * The filters live in the address (?skill=grammar&level=A1&search=…&page=2),
 * so a filtered list can be shared. `makeRoute(query)` builds that page's link.
 */
export function useLessonList(makeRoute) {
  const route = useRoute();
  const router = useRouter();
  const { locale } = useI18n({ useScope: "global" });

  // Current filters, read from the address
  const filters = computed(() => ({
    skill: SKILLS.some((skill) => skill.value === route.query.skill) ? route.query.skill : null,
    level: typeof route.query.level === "string" ? route.query.level : null,
    search: typeof route.query.search === "string" ? route.query.search : "",
    page: Math.max(1, Number(route.query.page) || 1),
  }));

  /** Change some filters; any change except the page goes back to page 1. */
  const setFilters = (changes) => {
    const next = { ...filters.value, page: 1, ...changes };
    const query = Object.fromEntries(Object.entries(next).filter(([key, value]) => value && !(key === "page" && value === 1)));
    router.replace(makeRoute(query));
  };

  const levels = ref([]);
  const lessons = ref([]);
  const meta = ref(null);
  const isLoading = ref(true);

  const load = async () => {
    isLoading.value = true;
    try {
      const { search, ...rest } = filters.value;
      const result = await getSiteLessons({ ...rest, search: search || null });
      lessons.value = result.data;
      meta.value = result.meta;
    } catch {
      lessons.value = [];
      meta.value = null;
    } finally {
      isLoading.value = false;
    }
  };

  watch(filters, load, { immediate: true, deep: true });
  onMounted(async () => {
    try {
      levels.value = await getSiteLevels();
    } catch {
      levels.value = [];
    }
  });

  // Search box: type freely, the list follows after a short pause
  const searchText = ref(filters.value.search);
  const applySearch = debounce(() => setFilters({ search: (searchText.value ?? "").trim() }), 350);
  watch(() => filters.value.search, (value) => {
    if (value !== (searchText.value ?? "").trim()) searchText.value = value;
  });

  const hasFilters = computed(() => Boolean(filters.value.skill || filters.value.level || filters.value.search));
  const clearFilters = () => {
    searchText.value = "";
    router.replace(makeRoute());
  };

  const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);

  const goToPage = (page) => {
    setFilters({ ...filters.value, page });
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return { filters, setFilters, levels, lessons, meta, isLoading, searchText, applySearch, hasFilters, clearFilters, levelName, goToPage };
}
