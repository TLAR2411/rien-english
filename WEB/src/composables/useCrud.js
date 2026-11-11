import { ref } from "vue";
import { api } from "@/utils/api";

/**
 * Save / delete / reorder rows of one API resource, e.g. useCrud("admin/levels").
 * Each action returns true when it worked. Error and success messages are shown
 * by the api interceptor (utils/api.js), so pages don't repeat that code.
 *
 *   const { isSaving, save, remove, move } = useCrud("admin/levels", { onDone: reloadTable });
 */
export function useCrud(resource, { onDone } = {}) {
  const isSaving = ref(false);

  const run = async (request) => {
    isSaving.value = true;
    try {
      const res = await request();
      onDone?.();
      return res.data?.data ?? true;
    } catch {
      return false;
    } finally {
      isSaving.value = false;
    }
  };

  /** Create (no id) or update (has id). */
  const save = (form) =>
    run(() =>
      form.id ? api.put(`${resource}/${form.id}`, form) : api.post(resource, form),
    );

  const remove = (item) => run(() => api.delete(`${resource}/${item.id}`));

  /** direction: "up" | "down" */
  const move = (item, direction) =>
    run(() => api.patch(`${resource}/${item.id}/move`, { direction }));

  return { isSaving, save, remove, move };
}
