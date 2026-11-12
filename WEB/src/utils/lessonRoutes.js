import hasPermission from "@/utils/hasPermission";

/**
 * Where "View" goes for a lesson (and its quiz): editors get the Preview tab
 * of the lesson page, people who can only view get the preview page.
 */
export const lessonPreviewRoute = (lessonId) =>
  hasPermission("edit-lessons")
    ? { name: "admin-lessons-id", params: { id: lessonId }, query: { tab: "preview" } }
    : { name: "admin-lessons-preview-id", params: { id: lessonId } };
