import { useTempAppStore } from "@/stores/tempAppStore";

/*
 * Lists for selects and look-ups.
 *
 * Reference lists that rarely change come from tempAppStore: fetched once,
 * then reused by every page, and refreshed automatically after a save or
 * delete on that resource (see CACHED_LISTS in the store). Pass `true` to
 * fetch one again anyway.
 */

const cached = (name) => (force = false) =>
    useTempAppStore().getList(name, force);

// ---- cached (tempAppStore) ---------------------------------------------------
export const getRoles = cached("roles");
export const getBranches = cached("branches");
export const getPermissions = cached("permissions");
export const getUsers = cached("getUsers");

