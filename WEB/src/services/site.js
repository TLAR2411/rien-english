import { api } from "@/utils/api";

/*
 * Public website API (no login): API/routes/modules/learning-site.route.php
 * Returns the `data` part of the reply.
 */

export const getSiteHome = async () => (await api.get("site/home")).data.data;
