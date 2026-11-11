import { setupLayouts } from "virtual:meta-layouts";
import { createRouter, createWebHistory } from "vue-router/auto";
import { redirects, routes } from "./additional-routes";
import { setupGuards } from "./guards";

function recursiveLayouts(route) {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i]);

    return route;
  }

  const layoutRoute = setupLayouts([route])[0];

  // Ensure the layout component can access layoutWrapperClasses
  if (route.meta?.layoutWrapperClasses) {
    layoutRoute.meta = {
      ...layoutRoute.meta,
      layoutWrapperClasses: route.meta.layoutWrapperClasses
    }
  }

  return layoutRoute;
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: "smooth", top: 60 };

    return { top: 0 };
  },
  extendRoutes: (pages) => [
    ...redirects,
    ...[...pages, ...routes].map((route) => recursiveLayouts(route)),
  ],
});

setupGuards(router);
export { router };
