/*
 * Links of the public website, in one place — so a page that moves later
 * (e.g. the sign-up page in T21) changes here only.
 */

export const homeRoute = () => ({ path: "/" });

/** The lesson list, optionally filtered: lessonsRoute({ skill: "grammar", level: "A1" }) */
export const lessonsRoute = (query = {}) => ({ path: "/lessons", query });

/** One lesson's page: /lessons/present-simple */
export const lessonRoute = (slug) => ({ path: `/lessons/${slug}` });

export const loginRoute = (redirect = null) => ({
  name: "login",
  query: redirect ? { redirect } : {},
});

// T21 adds the sign-up page. Until then "Sign up" opens the login page.
export const signUpRoute = (redirect = null) => loginRoute(redirect);

/** A page inside this app to go back to after login (never another website). */
export const safeRedirect = (value) =>
  typeof value === "string" && value.startsWith("/") && !value.startsWith("//") ? value : null;
