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
