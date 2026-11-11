import { api } from "@/utils/api";

/*
 * Public website API (no login): API/routes/modules/learning-site.route.php
 * Returns the `data` part of the reply.
 */

export const getSiteHome = async () => (await api.get("site/home")).data.data;

export const getSiteLevels = async () => (await api.get("site/levels")).data.data;

/** Lessons page: { data, meta } — params: skill, level (code), search, page */
export const getSiteLessons = async (params) => (await api.get("site/lessons", { params })).data.data;

/** One lesson to read (no quiz questions). A missing or draft lesson → 404, no error pop-up. */
export const getSiteLesson = async (slug) => (await api.get(`site/lessons/${encodeURIComponent(slug)}`, { silent: true })).data.data;

/* Students only: API/routes/modules/learning-student.route.php */

/** Mark a lesson as completed → { completed_at } */
export const completeLesson = async (lessonId) => (await api.post(`student/lessons/${lessonId}/complete`)).data.data;

/** Student dashboard → { next_lesson, stats, recent } */
export const getStudentDashboard = async () => (await api.get("student/dashboard")).data.data;

/** Finished lessons grouped by course → [{ course, level, lessons }] */
export const getMyLessons = async () => (await api.get("student/my-lessons")).data.data;
