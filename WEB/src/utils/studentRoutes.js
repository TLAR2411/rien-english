/*
 * Links inside the student system (side-menu layout), in one place.
 * Students stay in their own system: these pages never open the public website.
 */

/** The lesson list, optionally filtered: studentLessonsRoute({ skill: "grammar", level: "A1" }) */
export const studentLessonsRoute = (query = {}) => ({ name: "student-lessons", query });

/** One lesson to read: /student/lessons/present-simple */
export const studentLessonRoute = (slug) => ({ name: "student-lessons-slug", params: { slug } });

export const studentDashboardRoute = () => ({ name: "student-dashboard" });

export const myLessonsRoute = () => ({ name: "student-my-lessons" });

/** Take a lesson's quiz: /student/quiz/present-simple */
export const studentQuizRoute = (slug) => ({ name: "student-quiz-slug", params: { slug } });

/** One quiz result (also old ones): /student/quiz-results/12 */
export const quizResultRoute = (id) => ({ name: "student-quiz-results-id", params: { id } });

export const myQuizzesRoute = () => ({ name: "student-my-quizzes" });
