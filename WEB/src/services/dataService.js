import { useTempAppStore } from "@/stores/tempAppStore";
import { api } from "@/utils/api";

/*
 * Lists for selects and look-ups.
 *
 * Reference lists that rarely change come from tempAppStore: fetched once,
 * then reused by every page, and refreshed automatically after a save or
 * delete on that resource (see CACHED_LISTS in the store). Pass `true` to
 * fetch one again anyway.
 *
 * Lists that change all the time or depend on the user / selected branch
 * are fetched fresh on every call.
 */

const cached = (name) => (force = false) =>
    useTempAppStore().getList(name, force);

const live = (url) => async () => {
    try {
        const response = await api.get(url);
        return response.data.data;
    } catch (error) {
        console.error("Server error: ", error);
    }
};

// ---- cached (tempAppStore) ---------------------------------------------------
export const getPositions = cached("positions");
export const getDepartments = cached("departments");
export const getRoles = cached("roles");
export const getBranches = cached("branches");
export const getPermissions = cached("permissions");
export const getUsers = cached("getUsers");
// ---- live (always fetched) ---------------------------------------------------
export const getUnderUsers = live("users-all-under-users");
