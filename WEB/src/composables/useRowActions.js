import hasPermission from "@/utils/hasPermission";

/*
 * Row actions for AppCardTable, defined once.
 *
 * Each entry's `key` generates everything else:
 *   key "receive" → props isReceive / btnReceive / canReceive,
 *                   emit "onReceive", handler actionHandlers.receive
 *
 * Adding a new action = adding one entry here. Array order is display order.
 *
 * Options:
 *   condition: true      → also adds a `<key>Condition` prop: (item) => boolean
 *   conditionProp        → custom name for that prop
 *   confirm              → "delete" | "ask": show a confirm dialog before emitting
 *   confirmMessage       → dialog title (translated)
 *   permissionProps      → declare several `can*` props; permission becomes an array
 *   permissionFrom       → read the permission from a different prop name
 *   button: false        → props + emit only, no button rendered
 *   ...anything else     → copied onto the button (checkTitle, firstColor, …)
 */
export const ROW_ACTIONS = [
    { key: "approve", title: "Approve", icon: "tabler-check", color: "success", condition: true },
    {
        key: "default", title: "Default", icon: "tabler-circuit-capacitor", color: "secondary",
        checkDefault: true, firstColor: "success", secondColor: "secondary",
    },
    {
        key: "disable", title: "Disable", icon: "tabler-cancel", color: "secondary",
        checkActive: true, checkIcon: true, checkTitle: true,
        firstColor: "success", secondColor: "error",
        firstTitle: "Active", secondTitle: "Inactive",
        firstIcon: "tabler-check", secondIcon: "tabler-cancel",
    },
    { key: "linkUser", title: "Link User", icon: "tabler-link", color: "success" },
    { key: "password", title: "Password", icon: "tabler-key", color: "secondary" },
    { key: "username", title: "Username", icon: "tabler-user", color: "success" },
    { key: "view", title: "View", icon: "tabler-eye", color: "success" },
    { key: "copy", title: "Copy", icon: "tabler-copy", color: "warning" },
    { key: "detail", title: "Detail", icon: "tabler-file-description", color: "success" },
    { key: "permission", title: "Permission", icon: "tabler-shield", color: "warning" },
    { key: "edit", title: "Edit", icon: "tabler-pencil", color: "warning", condition: true },
    // Draft ⇄ Published switch (uses the edit permission)
    {
        key: "publish", title: "Publish", icon: "tabler-world-upload", color: "success",
        permissionFrom: "canEdit", checkPublished: true,
        firstColor: "warning", secondColor: "success",
        firstTitle: "Move to draft", secondTitle: "Publish",
        firstIcon: "tabler-world-off", secondIcon: "tabler-world-upload",
    },
    // Reorder a row (uses the edit permission)
    { key: "moveUp", title: "Move up", icon: "tabler-arrow-up", color: "secondary", permissionFrom: "canEdit" },
    { key: "moveDown", title: "Move down", icon: "tabler-arrow-down", color: "secondary", permissionFrom: "canEdit" },
    {
        key: "rollback", title: "Rollback", icon: "tabler-arrow-back-up", color: "error",
        condition: true, confirm: "ask", confirmMessage: "Rollback Item?",
    },
    {
        key: "delete", title: "Delete", icon: "tabler-trash", color: "error",
        condition: true, confirm: "delete", confirmMessage: "Delete Item?",
        isDelete: true,
    },

    // props and emits only, no button yet
    { key: "print", button: false },
    { key: "accept", button: false },
];

// ---------------------------------------------------------------------------

const OPTION_KEYS = [
    "key", "condition", "conditionProp", "confirm", "confirmMessage",
    "permissionProps", "permissionFrom", "button",
];

const pascal = (s) => s.charAt(0).toUpperCase() + s.slice(1);
const kebab = (s) => s.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);
const conditionPropName = (a) => a.conditionProp || `${a.key}Condition`;
const emitName = (a) => `on${pascal(a.key)}`;

const buildProps = () => {
    const props = {};

    for (const a of ROW_ACTIONS) {
        const P = pascal(a.key);

        props[`is${P}`] = Boolean;
        // Boolean first: a bare `btn-edit` attribute still means true
        props[`btn${P}`] = [Boolean, String];
        for (const name of a.permissionProps || [`can${P}`]) props[name] = String;
        if (a.condition)
            props[conditionPropName(a)] = { type: [Function, Boolean], default: null };
        // (item) => text: a per-row confirm title, e.g. naming what is deleted
        if (a.confirm)
            props[`${a.key}ConfirmMessage`] = { type: Function, default: null };
    }

    props.isMoreAction = { type: Boolean, default: true };
    props.isMoreActionCondition = { type: [Function, Boolean], default: null };

    return props;
};

export const dataTableProps = buildProps();

export const dataTableEmits = [...ROW_ACTIONS.map(emitName), "onPaginate"];

export function useRowActions(emit, props, { t, showDialog, debounce, DEBOUNCE_DELAY }) {
    const makeHandler = (a) => {
        const name = emitName(a);

        if (a.confirm) {
            const dialog =
                a.confirm === "delete"
                    ? { icon: "delete", confirmColor: "error", confirmText: "Delete" }
                    : { icon: "warning", confirmColor: "error" };

            return async (item) => {
                const custom = props?.[`${a.key}ConfirmMessage`];
                const title = custom ? custom(item) : t(a.confirmMessage);
                const ok = await showDialog({ title, ...dialog });
                if (ok) emit(name, item);
            };
        }

        return debounce((item) => emit(name, item), DEBOUNCE_DELAY);
    };

    const actionHandlers = Object.fromEntries(
        ROW_ACTIONS.map((a) => [a.key, makeHandler(a)]),
    );
    // old name for the disable handler, kept in case a page uses it
    actionHandlers.lock = actionHandlers.disable;

    const actionButtons = ROW_ACTIONS.filter((a) => a.button !== false).map((a) => {
        const P = pascal(a.key);

        // everything that isn't a config option is a display field for the button
        const display = Object.fromEntries(
            Object.entries(a).filter(([k]) => !OPTION_KEYS.includes(k)),
        );

        return {
            ...display,
            value: kebab(a.key),
            permission: a.permissionProps
                ? a.permissionProps.map((name) => props?.[name])
                : props?.[a.permissionFrom || `can${P}`],
            show: props?.[`is${P}`],
            btn: props?.[`btn${P}`],
            action: actionHandlers[a.key],
            condition: a.condition ? props?.[conditionPropName(a)] || null : null,
        };
    });

    return { actionHandlers, actionButtons };
}

// ---------------------------------------------------------------------------
// Per-row display helpers. "first" = the row is on (active / default / published).

const isOn = (button, item) => {
    if (button.checkPublished) return item.status === "published";
    if (button.checkDefault) return Boolean(item.default);
    return Boolean(item.is_active);
};

export const getButtonColor = (button, item) => {
    if (button.checkActive || button.checkDefault || button.checkPublished)
        return isOn(button, item) ? button.firstColor : button.secondColor;
    return button.color;
};

export const getButtonTitle = (button, item) => {
    if (button.checkTitle || button.checkPublished)
        return isOn(button, item) ? button.firstTitle : button.secondTitle;
    return button.title;
};

export const getButtonIcon = (button, item) => {
    if (button.checkIcon || button.checkPublished)
        return isOn(button, item) ? button.firstIcon : button.secondIcon;
    return button.icon;
};

export const checkPermission = (button) => hasPermission(button.permission);