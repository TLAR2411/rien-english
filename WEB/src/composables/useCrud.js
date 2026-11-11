import { ref } from "vue";
import { api } from "@/utils/api";

/**
 * Save / delete / reorder rows of one API resource, e.g. useCrud("admin/levels").
 * Each action returns true when it worked. Error and success messages are shown
 * by the api interceptor (utils/api.js), so pages don't repeat that code.
 *
 *   const { isSaving, save, remove, move } = useCrud("admin/levels", { onDone: reloadTable });
 *
 * `createUrl`: where new rows are posted when it is not `resource`
 * (e.g. examples are created under their lesson: "admin/lessons/5/examples").
 * It may be a function, for a URL only known later (e.g. the quiz id).
 */
export function useCrud(resource, { onDone, createUrl } = {}) {
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

  const createTarget = () =>
    (typeof createUrl === "function" ? createUrl() : createUrl) ?? resource;

  /** Create (no id) or update (has id). */
  const save = (form) =>
    run(() =>
      form.id ? api.put(`${resource}/${form.id}`, form) : api.post(createTarget(), form),
    );

  const remove = (item) => run(() => api.delete(`${resource}/${item.id}`));

  /** direction: "up" | "down" */
  const move = (item, direction) =>
    run(() => api.patch(`${resource}/${item.id}/move`, { direction }));

  return { isSaving, save, remove, move };
}
