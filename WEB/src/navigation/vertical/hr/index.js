import { computed } from 'vue';

/**
 * Creates the reactive part of the navigation related to HR.
 */
export const useHrNavigation = () => {
    const navigationItems = computed(() => [
        {
            title: "Dashboards",
            to: { name: "hr-dashboards" },
            icon: { icon: "tabler-layout-dashboard" },
        },
        { heading: 'Employees', permission: "view-employees" },
        {
            title: "List Employees",
            to: { name: "hr-employees" },
            icon: { icon: "tabler-address-book" },
            permission: "view-employees",
        },
        {
            title: "Former Employees",
            to: { name: "hr-former-employees" },
            icon: { icon: "tabler-user-off" },
            permission: "view-employees",
        },
    ]);

    return navigationItems;
};
