import { computed } from 'vue';

/**
 * Creates the reactive part of the navigation related to admin.
 */
export const useAdminNavigation = () => {
    const navigationItems = computed(() => [
        {
            title: "Dashboards",
            to: { name: "admin-dashboards" },
            icon: { icon: "tabler-layout-dashboard" },
        },
        {
            title: "List Users",
            to: { name: "admin-users" },
            icon: { icon: "tabler-users-group" },
            permission: "view-users"
        },
        {
            title: "Activity Log",
            to: { name: "admin-activity-log" },
            icon: { icon: "tabler-history" },
            permission: "view-activity-log"
        },

        {
            title: "Auth",

            icon: { icon: "tabler-shield-lock" },
            permission: '',
            children: [
                {
                    title: "Roles",
                    to: {
                        name: "admin-roles",
                    },
                    icon: { icon: "tabler-user-shield" },
                    permission: "view-roles",
                },
            ],
        },
        {
            title: "Generals",

            icon: { icon: "tabler-adjustments-horizontal" },
            permission: '',
            children: [
                {
                    title: "Branches",
                    to: {
                        name: "admin-branches",
                    },
                    icon: { icon: "tabler-building" },
                    permission: "view-branches",
                },
                {
                    title: "Settings",
                    to: {
                        name: "admin-settings",
                    },
                    icon: { icon: "tabler-settings" },
                    permission: "view-settings",
                },
            ],
        },
        {
            title: "Address",

            icon: { icon: "tabler-map-pin" },
            permission: '',
            children: [
                {
                    title: "Villages",
                    to: {
                        name: "admin-address-villages",
                    },
                    icon: { icon: "tabler-home" },
                    permission: "view-villages",
                },
                {
                    title: "Communes",
                    to: {
                        name: "admin-address-communes",
                    },
                    icon: { icon: "tabler-building-community" },
                    permission: "view-communes",
                },
                {
                    title: "Districts",
                    to: {
                        name: "admin-address-districts",
                    },
                    icon: { icon: "tabler-map-2" },
                    permission: "view-districts",
                },
                {
                    title: "Provinces",
                    to: {
                        name: "admin-address-provinces",
                    },
                    icon: { icon: "tabler-map" },
                    permission: "view-provinces",
                },
            ],
        },
    ]);

    return navigationItems;
};
