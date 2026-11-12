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

/** A lesson's quiz to take, by the lesson slug: questions without answers. Missing → 404, no pop-up. */
export const getStudentQuiz = async (slug) =>
  (await api.get(`student/lessons/${encodeURIComponent(slug)}/quiz`, { silent: true })).data.data;

/** Send the answers ({ answers: [{ question_id, answer }], started_at }) → the graded result */
export const submitQuiz = async (quizId, payload) => (await api.post(`student/quizzes/${quizId}/submit`, payload)).data.data;

/** My Progress → { skills, levels, quizzes } */
export const getMyProgress = async () => (await api.get("student/progress")).data.data;

/** My Quizzes: { data, meta } — params: page */
export const getQuizAttempts = async (params) => (await api.get("student/quiz-attempts", { params })).data.data;

/** One quiz result with the correct answers. Not mine / missing → 404, no pop-up. */
export const getQuizResult = async (id) => (await api.get(`student/quiz-attempts/${id}`, { silent: true })).data.data;

/** My writings for a writing lesson (newest first) + the sample answer once I sent one. Missing → 404, no pop-up. */
export const getMyWriting = async (slug) =>
  (await api.get(`student/lessons/${encodeURIComponent(slug)}/writing`, { silent: true })).data.data;

/** Send a writing → { submission, sample_answer }. Errors (word target, already waiting) come back to the page. */
export const submitWriting = async (slug, text) =>
  (await api.post(`student/lessons/${encodeURIComponent(slug)}/writing`, { text }, { silent: true })).data.data;

/** My Writing: { data, meta } — params: page */
export const getMyWritings = async (params) => (await api.get("student/writings", { params })).data.data;

/** One of my writings + the lesson's task and sample answer. Not mine / missing → 404, no pop-up. */
export const getMyWritingOne = async (id) => (await api.get(`student/writings/${id}`, { silent: true })).data.data;
