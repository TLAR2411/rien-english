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

/* Writing Review (T34) — API/app/Http/Controllers/Api/Learning/Admin/WritingReviewController.php */

/** API address of the writings list (paginated, for AppCardTable; filter[status], filter[search]) */
export const WRITINGS_URL = "admin/writings";

/** One writing with the lesson's task, earlier writings, next_pending_id. Missing → 404, no pop-up. */
export const getWritingReview = async (id) => (await api.get(`admin/writings/${id}`, { silent: true })).data.data;

/** Save { score, feedback } → the writing + next_pending_id ("Saved." pop-up) */
export const saveWritingReview = async (id, payload) => (await api.put(`admin/writings/${id}/review`, payload)).data.data;
