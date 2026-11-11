<script setup>
import LessonPreview from "@/views/admin/learning/lessons/preview/LessonPreview.vue";
import StatusChip from "@/components/learning/StatusChip.vue";
import hasPermission from "@/utils/hasPermission";

definePage({
  meta: {
    title: "Preview",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-lessons",
  },
});

// For people who can see lessons but not edit them.
// Editors see the same preview in the "Preview" tab of the lesson page.
const route = useRoute();
const status = ref(null);
const canEdit = hasPermission("edit-lessons");
</script>

<template>
  <AppCard title="Preview" title-icon="tabler-eye">
    <template #card-header>
      <StatusChip v-if="status" :status="status" class="me-2" />
      <IconBtn
        v-if="canEdit"
        rounded
        size="30"
        class="mr-1"
        :aria-label="$t('Back to editing')"
        :to="{ name: 'admin-lessons-id', params: { id: route.params.id } }"
      >
        <VIcon icon="tabler-pencil" size="20" />
        <VTooltip location="top" activator="parent">{{ $t("Back to editing") }}</VTooltip>
      </IconBtn>
    </template>

    <LessonPreview :lesson-id="route.params.id" @loaded="(saved) => (status = saved.status)" />
  </AppCard>
</template>
