/*
 * Links of the public website, in one place — so a page that moves later
 * later changes here only.
 */

export const homeRoute = () => ({ path: "/" });

/** The lesson list, optionally filtered: lessonsRoute({ skill: "grammar", level: "A1" }) */
export const lessonsRoute = (query = {}) => ({ path: "/lessons", query });

/** One lesson's page: /lessons/present-simple */
export const lessonRoute = (slug) => ({ path: `/lessons/${slug}` });

/** A lesson's quiz. Needs login (T25 builds the quiz page). */
export const quizRoute = (slug) => ({ path: `/student/quiz/${slug}` });

export const loginRoute = (redirect = null) => ({
  name: "login",
  query: redirect ? { redirect } : {},
});

export const signUpRoute = (redirect = null) => ({
  name: "signup",
  query: redirect ? { redirect } : {},
});

/** A page inside this app to go back to after login (never another website). */
export const safeRedirect = (value) =>
  typeof value === "string" && value.startsWith("/") && !value.startsWith("//") ? value : null;
