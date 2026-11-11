// Dashboard a user lands on for their default part (users.default_part):
// used after login / switching user, and when the app is opened at "/"
// (e.g. the installed PWA, whose start_url is "/").
const HOME_BY_PART = {
  admin: "/admin",
};

export const homePathFor = (part) => HOME_BY_PART[part] ?? "/admin";

export default homePathFor;
