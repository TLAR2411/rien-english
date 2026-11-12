<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import AppDialog from "@/components/AppDialog.vue";
import { requiredValidator } from "@/@core/utils/validators";

// A quiz belongs to one lesson: pick a lesson that has no quiz yet,
// then its Quiz tab opens to build it.
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
});

const emit = defineEmits(["update:isDialogVisible"]);

const { t } = useI18n();
const router = useRouter();
const lessons = ref([]);
const lessonId = ref(null);
const isLoading = ref(false);

const load = async () => {
  isLoading.value = true;
  try {
    const res = await api.get("admin/lessons", { params: { "filter[without_quiz]": 1, limit: 200 } });
    lessons.value = res.data.data.data;
  } finally {
    isLoading.value = false;
  }
};

watch(
  () => props.isDialogVisible,
  (open) => {
    lessonId.value = null;
    if (open) load();
  },
);

const lessonTitle = (lesson) => `${lesson.course?.level?.code ?? ""} · ${lesson.course?.title ?? ""} · ${lesson.title_en}`;

const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid) return;
  emit("update:isDialogVisible", false);
  router.push({ name: "admin-lessons-id", params: { id: lessonId.value }, query: { tab: "quiz" } });
};
</script>

<template>
  <AppDialog
    title="New Quiz"
    title-icon="tabler-checklist"
    :is-dialog-visible="isDialogVisible"
    @update:is-dialog-visible="(v) => emit('update:isDialogVisible', v)"
    :width="560"
    is-footer
    is-submit
    submit-text="Next"
    submit-icon="tabler-arrow-right"
    submit-color="primary"
    :loading="isLoading"
    @on-submit="onSubmit"
  >
    <VRow>
      <VCol cols="12">
        <AppAutocomplete
          v-model="lessonId"
          label="Lesson"
          :items="lessons"
          :item-title="lessonTitle"
          item-value="id"
          :rules="[requiredValidator]"
          :no-data-text="t('Every lesson already has a quiz.')"
          :hint="t('Each lesson has one quiz. Only lessons without a quiz are shown.')"
          persistent-hint
        />
      </VCol>
    </VRow>
  </AppDialog>
</template>
