import { api } from "@/utils/api";

/*
 * Admin calls for learning numbers (dashboard, a user's scores).
 * The API sends only what the admin's permissions allow.
 */

/** Admin dashboard numbers → { students, new_students, active_students, quizzes_taken, average_percent, popular_lessons, … } */
export const getAdminDashboard = async () => (await api.get("admin/dashboard")).data.data;

/** One user's learning → { user, stats, skills, quizzes }. Missing / other branch → 404, no pop-up. */
export const getUserLearning = async (userId) => (await api.get(`users/${userId}/learning`, { silent: true })).data.data;

/** API address of every quiz try of a user (paginated, for AppCardTable) */
export const userQuizAttemptsUrl = (userId) => `users/${userId}/quiz-attempts`;
