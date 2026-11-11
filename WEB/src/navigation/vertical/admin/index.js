import { computed } from "vue";

/**
 * Admin sidebar. Each item is shown only if the user has its `permission`
 * (roles get their permissions from API/config/roles.php).
 */
export const useAdminNavigation = () => {
  const navigationItems = computed(() => [
    {
      title: "Dashboard",
      to: { name: "admin-dashboards" },
      icon: { icon: "tabler-layout-dashboard" },
    },

    // ---- Learning content (admin + teacher) ----
    { heading: "Learning", permission: ["view-levels", "view-courses", "view-lessons"] },
    {
      title: "Levels",
      to: { name: "admin-levels" },
      icon: { icon: "tabler-stairs" },
      permission: "view-levels",
    },
    {
      title: "Courses",
      to: { name: "admin-courses" },
      icon: { icon: "tabler-books" },
      permission: "view-courses",
    },
    {
      title: "Lessons",
      to: { name: "admin-lessons" },
      icon: { icon: "tabler-notebook" },
      permission: "view-lessons",
    },

    // ---- People (admin) ----
    { heading: "People", permission: "view-users" },
    {
      title: "Users",
      to: { name: "admin-users" },
      icon: { icon: "tabler-users-group" },
      permission: "view-users",
    },

    // ---- System (admin) ----
    {
      heading: "System",
      permission: ["view-roles", "view-branches", "view-settings", "view-activity-log"],
    },
    {
      title: "Roles",
      to: { name: "admin-roles" },
      icon: { icon: "tabler-user-shield" },
      permission: "view-roles",
    },
    {
      title: "Branches",
      to: { name: "admin-branches" },
      icon: { icon: "tabler-building" },
      permission: "view-branches",
    },
    {
      title: "Settings",
      to: { name: "admin-settings" },
      icon: { icon: "tabler-settings" },
      permission: "view-settings",
    },
    {
      title: "Activity Log",
      to: { name: "admin-activity-log" },
      icon: { icon: "tabler-history" },
      permission: "view-activity-log",
    },
    {
      title: "Address",
      icon: { icon: "tabler-map-pin" },
      permission: ["view-villages", "view-communes", "view-districts", "view-provinces"],
      children: [
        {
          title: "Villages",
          to: { name: "admin-address-villages" },
          icon: { icon: "tabler-home" },
          permission: "view-villages",
        },
        {
          title: "Communes",
          to: { name: "admin-address-communes" },
          icon: { icon: "tabler-building-community" },
          permission: "view-communes",
        },
        {
          title: "Districts",
          to: { name: "admin-address-districts" },
          icon: { icon: "tabler-map-2" },
          permission: "view-districts",
        },
        {
          title: "Provinces",
          to: { name: "admin-address-provinces" },
          icon: { icon: "tabler-map" },
          permission: "view-provinces",
        },
      ],
    },
  ]);

  return navigationItems;
};
