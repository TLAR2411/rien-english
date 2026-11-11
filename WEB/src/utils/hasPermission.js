import { auth } from "/src/utils/auth.js";

function hasPermission(requiredPermission) {
    // userPermissions is now just an array of strings:
    // ["view-branches", "add-branches", "edit-users", ...]
    const userPermissions = auth()?.permissions || [];

    if (Array.isArray(requiredPermission)) {
        // Check if user has **any** of the required permissions
        // We just check if any perm string is in the userPermissions array
        return requiredPermission.some((perm) => userPermissions.includes(perm));
    } else {
        // Single permission check
        // We just check if the perm string is in the userPermissions array
        return userPermissions.includes(requiredPermission);
    }
}

export default hasPermission;