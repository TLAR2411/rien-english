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
    { key: "activityCreate", title: "Activity Create", icon: "tabler-pencil-plus", color: "success" },
    { key: "activityHistory", title: "Activity History", icon: "tabler-history", color: "primary" },
    { key: "clientLoanHistory", title: "Client Loan History", icon: "tabler-history", color: "primary" },
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
    { key: "receive", title: "Receive", icon: "tabler-cash-banknote", color: "success", condition: true },
    { key: "salary", title: "Edit Salary", icon: "tabler-cash-edit", color: "primary" },
    // promotion / demotion / transfer / salary adjustment
    { key: "jobChange", title: "Promotion / Demotion", icon: "tabler-arrows-up-down", color: "primary" },
    { key: "terminate", title: "Terminate", icon: "tabler-file-x", color: "error" },
    {
        key: "receiveLastSchedule", title: "Receive Last Schedule", icon: "tabler-cash-banknote",
        color: "success", condition: true,
        checkStatus: true, checkIcon: true, checkTitle: true, checkDisable: true,
        firstColor: "success", secondColor: "secondary",
        firstTitle: "Receive Last Schedule", secondTitle: "Has Receive",
        firstIcon: "tabler-cash-banknote", secondIcon: "tabler-file-check",
    },
    {
        key: "backDailyCash", title: "Back Daily Cash", icon: "tabler-arrow-back", color: "secondary",
        condition: true, confirm: "ask", confirmMessage: "Back to daily cash?",
    },
    { key: "repay", title: "Repay", icon: "tabler-cash-banknote", color: "success", condition: true },
    { key: "view", title: "View", icon: "tabler-eye", color: "success" },
    { key: "payoffRequest", title: "Payoff Request", icon: "tabler-ballpen", color: "warning" },
    { key: "copy", title: "Copy", icon: "tabler-copy", color: "warning" },
    // rental stays
    { key: "moveIn", title: "Move In", icon: "tabler-login-2", color: "primary", condition: true },
    { key: "moveOut", title: "Move Out", icon: "tabler-logout", color: "primary", condition: true },
    { key: "changeDates", title: "Change Dates", icon: "tabler-calendar-event", color: "secondary", condition: true },
    { key: "cancelStay", title: "Cancel Stay", icon: "tabler-calendar-off", color: "error", condition: true },
    { key: "reminderLetter", title: "Reminder Letter", icon: "tabler-bell-ringing", color: "secondary" },
    { key: "requestPawnLetter", title: "Request Pawn Letter", icon: "tabler-news", color: "secondary" },
    { key: "recovery", title: "Recovery", icon: "tabler-restore", color: "success" },
    { key: "detail", title: "Detail", icon: "tabler-file-description", color: "success" },
    { key: "schedule", title: "Schedule", icon: "tabler-calendar-event", color: "warning", condition: true },
    {
        key: "scheduleForClient", title: "Schedule For Client", icon: "tabler-calendar-event",
        color: "warning", condition: true,
    },
    {
        key: "clientHistory", title: "Client History", icon: "tabler-user-search", color: "success",
        // TODO: typo carried over from the old file, so this permission is
        // never checked. Delete this line to use canClientHistory.
        permissionFrom: "canClientistory",
    },
    {
        key: "blackList", title: "Black List", icon: "tabler-cancel", color: "error",
        confirm: "ask", confirmMessage: "Do you want to make this client to black list?",
        permissionProps: ["canCheckBlackList", "canUncheckBlackList"],
        checkBlackList: true, checkBleckListPermission: true, checkIcon: true,
        firstColor: "error", secondColor: "secondary",
        firstIcon: "tabler-address-book-off", secondIcon: "tabler-address-book",
    },
    {
        key: "receiveHistory", title: "Receive History", icon: "tabler-history", color: "success",
        // TODO: canLoan is not a prop, so this permission is never checked.
        // Delete this line to use canReceiveHistory.
        permissionFrom: "canLoan",
    },
    { key: "penalty", title: "Penalty", icon: "tabler-coin", color: "warning" },
    { key: "restructure", title: "Restructure", icon: "tabler-calendar-event", color: "warning" },
    { key: "permission", title: "Permission", icon: "tabler-shield", color: "warning" },
    { key: "edit", title: "Edit", icon: "tabler-pencil", color: "warning", condition: true },
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
// Per-row display helpers (unchanged)

export const getButtonColor = (button, item) => {
    if (button.checkBlackList) return item.is_black_list ? button.firstColor : button.secondColor;
    if (button.checkActive)
        return item.is_active || item.last_schedule?.status ? button.firstColor : button.secondColor;
    if (button.checkStatus)
        return item.last_schedule?.status ? button.firstColor : button.secondColor;
    if (button.checkDefault) return item.default ? button.firstColor : button.secondColor;
    return button.color;
};

export const getButtonTitle = (button, item) => {
    if (button.checkTitle)
        return item.is_active || item.last_schedule?.status ? button.firstTitle : button.secondTitle;
    return button.title;
};

export const getButtonDisable = (button, item) => {
    if (button.checkDisable) return !item.last_schedule?.status;
    return false;
};

export const getButtonIcon = (button, item) => {
    if (button.checkBlackList) return item.is_black_list ? button.firstIcon : button.secondIcon;
    if (button.checkIcon)
        return item.is_active || item.last_schedule?.status ? button.firstIcon : button.secondIcon;
    return button.icon;
};

export const checkPermission = (button, item) => {
    if (button.checkBleckListPermission)
        return item.is_black_list
            ? hasPermission(button.permission[1])
            : hasPermission(button.permission[0]);
    return hasPermission(button.permission);
};