<script setup>
definePage({
  meta: {
    title: "Quizzes",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-quizzes",
  },
});

// Quizzes now live in a tab of the Lessons page; keep old links working.
const router = useRouter();
router.replace({ name: "admin-lessons", query: { tab: "quizzes" } });
</script>

<template>
  <div />
</template>
