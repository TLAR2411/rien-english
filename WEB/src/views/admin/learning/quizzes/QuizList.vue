<script setup>
import { useI18n } from "vue-i18n";
import { getCourses, getLevels } from "@/services/dataService";
import StatusChip from "@/components/learning/StatusChip.vue";
import AddQuizDialog from "@/views/admin/learning/quizzes/AddQuizDialog.vue";
import { lessonPreviewRoute } from "@/utils/lessonRoutes";

// Every lesson quiz. A quiz is built on its lesson page (Quiz tab);
// "Add" picks a lesson without a quiz and opens that tab.
const { t } = useI18n();
const router = useRouter();
const isLoading = ref(false);
const isAddVisible = ref(false);
const levels = ref([]);
const courses = ref([]);
const filter = ref({ search: null, level_id: null, course_id: null });

onMounted(async () => {
  [levels.value, courses.value] = await Promise.all([getLevels(), getCourses()]);
});

const levelCourses = computed(() =>
  filter.value.level_id ? courses.value.filter((c) => c.level_id === filter.value.level_id) : courses.value,
);

const headers = [
  { title: t("Quiz title"), key: "title", visible: true },
  { title: t("Lesson"), key: "lesson", visible: true },
  { title: t("Questions"), key: "questions_count", visible: true },
  { title: t("Pass score"), key: "pass_score", visible: true },
  { title: t("Lesson status"), key: "status", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
];

const viewQuiz = (quiz) => router.push(lessonPreviewRoute(quiz.lesson_id));
const openQuiz = (quiz) =>
  router.push({ name: "admin-lessons-id", params: { id: quiz.lesson_id }, query: { tab: "quiz" } });
</script>

<template>
  <AddQuizDialog v-model:is-dialog-visible="isAddVisible" />

  <AppCardTable
    v-model:is-dialog-create-visible="isAddVisible"
    v-model:loading="isLoading"
    v-model:filters="filter"
    title="Quizzes"
    border=""
    is-full-height-tab
    title-icon="tabler-checklist"
    api-url="admin/quizzes"
    :headers="headers"
    save-header-name="header-quizzes-list"
    enable-mobile-card
    is-filter
    create-dialog
    can-create="edit-quizzes"
    is-view
    can-view="view-quizzes"
    is-edit
    can-edit="edit-quizzes"
    :is-back="false"
    @on-view="viewQuiz"
    @on-edit="openQuiz"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="6" sm="4" lg="2">
          <AppSelect v-model="filter.level_id" :items="levels" item-title="code" item-value="id" :placeholder="t('Level')" clearable />
        </VCol>
        <VCol cols="6" sm="4" lg="2">
          <AppSelect v-model="filter.course_id" :items="levelCourses" item-title="title" item-value="id" :placeholder="t('Course')" clearable />
        </VCol>
        <VCol cols="12" sm="4" lg="3">
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

    <template #item.lesson="{ item }">
      <span class="text-no-wrap">{{ item.lesson?.course?.level?.code }} · {{ item.lesson?.title_en }}</span>
    </template>
    <template #item.pass_score="{ item }">{{ item.pass_score }}%</template>
    <template #item.status="{ item }"><StatusChip v-if="item.lesson" :status="item.lesson.status" /></template>
  </AppCardTable>
</template>
