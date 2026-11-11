import { api } from "@/utils/api";

/*
 * Public website API (no login): API/routes/modules/learning-site.route.php
 * Returns the `data` part of the reply.
 */

export const getSiteHome = async () => (await api.get("site/home")).data.data;

export const getSiteLevels = async () => (await api.get("site/levels")).data.data;

/** Lessons page: { data, meta } — params: skill, level (code), search, page */
export const getSiteLessons = async (params) => (await api.get("site/lessons", { params })).data.data;
