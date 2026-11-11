<script setup>
import { useI18n } from "vue-i18n";
import { useCrud } from "@/composables/useCrud";
import { getCourses, getLevels } from "@/services/dataService";
import { PUBLISH_STATUSES, SKILLS, findOption } from "@/constants/learning";
import StatusChip from "@/components/learning/StatusChip.vue";
import { lessonPreviewRoute } from "@/utils/lessonRoutes";

const { t } = useI18n();
const router = useRouter();
const tableRef = ref(null);
const isLoading = ref(false);
const levels = ref([]);
const courses = ref([]);
const filter = ref({ search: null, level_id: null, course_id: null, skill: null, status: null });

const { remove, move } = useCrud("admin/lessons", {
  onDone: () => tableRef.value?.reload(),
});

onMounted(async () => {
  [levels.value, courses.value] = await Promise.all([getLevels(), getCourses()]);
});

// Course filter shows only the courses of the chosen level
const levelCourses = computed(() =>
  filter.value.level_id
    ? courses.value.filter((course) => course.level_id === filter.value.level_id)
    : courses.value,
);
watch(
  () => filter.value.level_id,
  () => {
    if (!levelCourses.value.some((course) => course.id === filter.value.course_id))
      filter.value.course_id = null;
  },
);

const headers = [
  { title: t("Title English"), key: "title_en", visible: true },
  { title: t("Title Khmer"), key: "title_km", visible: true },
  { title: t("Course"), key: "course", visible: true },
  { title: t("Skill"), key: "skill", visible: true },
  { title: t("Examples"), key: "examples_count", visible: true },
  { title: t("Status"), key: "status", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const onEdit = (lesson) => router.push({ name: "admin-lessons-id", params: { id: lesson.id } });
const onView = (lesson) => router.push(lessonPreviewRoute(lesson.id));

const skillTitle = (value) => t(findOption(SKILLS, value)?.title ?? value);
</script>

<template>
  <AppCardTable
    ref="tableRef"
    v-model:loading="isLoading"
    v-model:filters="filter"
    title="Lessons"
    border=""
    is-full-height-tab
    title-icon="tabler-notebook"
    api-url="admin/lessons"
    :headers="headers"
    save-header-name="header-lessons-list"
    enable-mobile-card
    is-filter
    create-page="admin-lessons-create"
    can-create="add-lessons"
    is-view
    can-view="view-lessons"
    is-edit
    is-move-up
    is-move-down
    can-edit="edit-lessons"
    is-delete
    can-delete="delete-lessons"
    :is-back="false"
    @on-view="onView"
    @on-edit="onEdit"
    @on-move-up="(item) => move(item, 'up')"
    @on-move-down="(item) => move(item, 'down')"
    @on-delete="remove"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="6" sm="4" lg="2">
          <AppSelect
            v-model="filter.level_id"
            :items="levels"
            item-title="code"
            item-value="id"
            :placeholder="t('Level')"
            clearable
          />
        </VCol>
        <VCol cols="6" sm="4" lg="2">
          <AppSelect
            v-model="filter.course_id"
            :items="levelCourses"
            item-title="title"
            item-value="id"
            :placeholder="t('Course')"
            clearable
          />
        </VCol>
        <VCol cols="6" sm="4" lg="2">
          <AppSelect
            v-model="filter.skill"
            :items="SKILLS"
            :item-title="(item) => t(item.title)"
            item-value="value"
            :placeholder="t('Skill')"
            clearable
          />
        </VCol>
        <VCol cols="6" sm="4" lg="2">
          <AppSelect
            v-model="filter.status"
            :items="PUBLISH_STATUSES"
            :item-title="(item) => t(item.title)"
            item-value="value"
            :placeholder="t('Status')"
            clearable
          />
        </VCol>
        <VCol cols="12" sm="8" lg="3">
          <VTextField
            v-model="filter.search"
            :label="t('Search')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>

    <template #item.course="{ item }">
      <span class="text-no-wrap">{{ item.course?.level?.code }} · {{ item.course?.title }}</span>
    </template>
    <template #item.skill="{ item }">{{ skillTitle(item.skill) }}</template>
    <template #item.status="{ item }"><StatusChip :status="item.status" /></template>
  </AppCardTable>
</template>
