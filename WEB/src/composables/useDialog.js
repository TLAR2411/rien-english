// composables/useDialog.js
import { ref } from "vue";

// Shared state (singleton)
const dialogRef = ref(null);

export function useDialog() {
    // Initialize the dialog reference (call this once in App.vue)
    const initializeDialog = (componentRef) => {
        dialogRef.value = componentRef;
    };

    // Open the dialog (or a toast) from anywhere.
    // `items` is an object — passed straight through so keys map by name,
    // not by argument position.
    const showDialog = async (items = {}) => {
        if (!dialogRef.value) {
            throw new Error("Dialog component not initialized!");
        }

        return await dialogRef.value.openDialog({
            title: items.title ?? "",
            icon: items.icon ?? null,
            heading: items.heading ?? null,
            // Optional big value under the message, e.g. the amount being saved
            highlight: items.highlight ?? null,
            // Small caption above that value, e.g. "Total"
            highlightLabel: items.highlightLabel ?? null,
            // Optional "from → to" block:
            // { from: { label, code, name }, to: { label, code, name }, amount }
            transfer: items.transfer ?? null,
            isCancel: items.isCancel ?? true,
            isConfirm: items.isConfirm ?? true,
            confirmColor: items.confirmColor ?? "primary",
            timer: items.timer ?? 0,
            cancelText: items.cancelText ?? "Close",
            confirmText: items.confirmText ?? "OK",
        });
    };

    return { initializeDialog, showDialog };
}