import { computed, unref } from "vue";
import { usePartStore } from "@/stores/partStore";
import DefaultNavItems from "@/navigation/vertical/index";
import { useAdminNavigation } from "@/navigation/vertical/admin";
import { useStudentNavigation } from "@/navigation/vertical/student";

// The normalized `system_part` value for each part — what
// usePartStore().system_part holds once router/guards.js applies
// SYSTEM_PART_SEGMENT_MAP (below) to the current route. Exported so other
// files that need to branch on the active system part (e.g. choosing a
// sidebar nav list) don't hardcode these strings themselves.
export const SYSTEM_PART = {
  ADMIN: "admin",
  STUDENT: "student",
};

/**
 * Single source of truth for the switchable "system parts" shown in the
 * user menu (see UserFullProfile.vue) — and, via `profileRouteName` /
 * `navItems`, where the profile icon sends each one and which sidebar it gets.
 *
 * `part`             — value passed to usePartStore().setSystemPart() when
 *                       the user clicks this item; also the route name it
 *                       navigates to.
 * `matchValue`        — the `system_part` value once the router guard
 *                       normalizes it (see SYSTEM_PART above). Compare
 *                       against this for "is this part active" checks.
 * `profileRouteName`  — the route the profile icon pushes to for this part.
 * `navItems`          — that part's `use*Navigation` composable (see
 *                       navigation/vertical/*), or omitted to fall back to
 *                       the plain default sidebar (navigation/vertical/index).
 *
 * Add a new system part here and it shows up in the user menu, gets a
 * profile route, and (if `navItems` is set) its own sidebar — automatically.
 */

// `navItems` below holds each part's `use*Navigation` composable *reference*,
// not its result — these read Pinia stores, so calling them here at module
// scope would run before app.use(pinia) does (see useSystemPartNavItems(),
// which calls each one lazily instead).
const SYSTEM_PARTS = [
  {
    title: "Admin System",
    icon: "tabler-shield-cog",
    part: "admin",
    matchValue: SYSTEM_PART.ADMIN,
    permission: "admin-allow-part",
    profileRouteName: "admin-user-profile-tab",
    navItems: useAdminNavigation,
  },
];

export function useSystemParts() {
  const setting = usePartStore();

  const systemParts = computed(() =>
    SYSTEM_PARTS.map((item) => ({
      ...item,
      active: setting.system_part === item.matchValue,
    })),
  );

  return { systemParts };
}

// Parts that exist (a URL segment, a `system_part` value, maybe a profile
// route) but aren't shown as a switchable item in the user menu.
// `part` doubles as the URL's first path segment for these.
//
// Student system (/student/...): students only use this part, so it is not
// in the switch menu. No permission here — the student API itself allows only
// the `student` role (API/config/roles.php → groups.student_area).
const HIDDEN_SYSTEM_PARTS = [
  {
    part: "student",
    matchValue: SYSTEM_PART.STUDENT,
    navItems: useStudentNavigation,
  },
];

const ALL_PARTS = [...SYSTEM_PARTS, ...HIDDEN_SYSTEM_PARTS];

// router/guards.js: maps the URL's first path segment to the normalized
// `system_part` value it stores (its old local `segmentMap`, now derived
// here so it can't drift out of sync with SYSTEM_PARTS / SYSTEM_PART).
export const SYSTEM_PART_SEGMENT_MAP = Object.fromEntries(
  ALL_PARTS.map((item) => [item.part, item.matchValue]),
);

// router/guards.js: the permission a user needs to open any page of a part
// (e.g. "admin-allow-part" — roles get it from API/config/roles.php).
export const PERMISSION_BY_PART = Object.fromEntries(
  ALL_PARTS.filter((item) => item.permission).map((item) => [
    item.matchValue,
    item.permission,
  ]),
);

// Where the profile icon (UserFullProfile.vue) sends each system part.
// Keyed by SYSTEM_PART so it can't drift the way an old hard-coded part name
// check did when the router guard normalized to a different string.
export const PROFILE_ROUTE_BY_PART = Object.fromEntries(
  ALL_PARTS.filter((item) => item.profileRouteName).map((item) => [
    item.matchValue,
    { name: item.profileRouteName, params: { tab: "account" } },
  ]),
);

// Which sidebar nav list to show for each system part, derived from
// each part's `navItems`. Parts without one fall back to the default sidebar.
const NAV_ITEMS_FACTORY_BY_PART = Object.fromEntries(
  ALL_PARTS.filter((item) => item.navItems).map((item) => [
    item.matchValue,
    item.navItems,
  ]),
);

export function useSystemPartNavItems() {
  const part = usePartStore();

  // Call every part's nav composable once here (inside a composable invoked
  // from a component's setup), not at module scope, so the Pinia stores they
  // read are guaranteed active.
  const navItemsByPart = Object.fromEntries(
    Object.entries(NAV_ITEMS_FACTORY_BY_PART).map(([matchValue, useNav]) => [
      matchValue,
      useNav(),
    ]),
  );

  return computed(
    () => unref(navItemsByPart[part.system_part]) ?? DefaultNavItems,
  );
}
