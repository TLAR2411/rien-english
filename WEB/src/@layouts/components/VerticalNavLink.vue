<script setup>
import { layoutConfig } from "@layouts";
import { can } from "@layouts/plugins/casl";
import { useLayoutConfigStore } from "@layouts/stores/config";
import {
  getComputedNavLinkToProp,
  getDynamicI18nProps,
  isNavLinkActive,
} from "@layouts/utils";
import hasPermission from "@/utils/hasPermission.js";
import { useRouter } from "vue-router";

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
});

const configStore = useLayoutConfigStore();
const hideTitleAndBadge = configStore.isVerticalNavMini();

// Optional quick action at the end of the link, e.g. "+" on Clients to open
// the new-client form directly. Nav item config:
//   shortcut: { to, icon?, title?, permission? }
const showShortcut = computed(
  () =>
    !!props.item.shortcut?.to &&
    !hideTitleAndBadge.value &&
    (!props.item.shortcut.permission ||
      hasPermission(props.item.shortcut.permission)),
);

const router = useRouter();
const openShortcut = () => router.push(props.item.shortcut.to);
</script>

<template>
  <li
    v-if="
      can(item.action, item.subject) &&
      (!item.permission || hasPermission(item.permission))
    "
    class="nav-link"
    :class="{ disabled: item.disable, 'has-shortcut': showShortcut }"
  >
    <Component
      :is="item.to ? 'RouterLink' : 'a'"
      v-bind="getComputedNavLinkToProp(item)"
      :class="{
        'router-link-active router-link-exact-active': isNavLinkActive(
          item,
          $router,
        ),
      }"
    >
      <Component
        :is="layoutConfig.app.iconRenderer || 'div'"
        v-bind="item.icon || layoutConfig.verticalNav.defaultNavItemIconProps"
        class="nav-item-icon"
      />
      <TransitionGroup name="transition-slide-x">
        <!-- 👉 Title -->
        <Component
          :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          v-show="!hideTitleAndBadge"
          key="title"
          class="nav-item-title pt-1"
          v-bind="getDynamicI18nProps(item.title, 'span')"
        >
          {{ item.title }}
        </Component>

        <!-- 👉 Badge -->
        <VBadge
          v-if="item.badge?.content"
          :content="item.badge?.content"
          :color="item.badge?.color"
          class="mr-2"
        >
        </VBadge>
        <!-- <Component
          :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          v-if="item.badgeContent"
          v-show="!hideTitleAndBadge"
          key="badge"
          class="nav-item-badge"
          :class="item.badgeClass"
          v-bind="getDynamicI18nProps(item.badgeContent, 'span')"
          style="width: 25px; border-radius: 50%"
        >
          {{ item.badgeContent }}
        </Component> -->
      </TransitionGroup>

      <!-- 👉 Shortcut: inside the link row so its flex centring lines it up
           exactly with the icon and title. A span with button behaviour
           (a real <button> may not sit inside a link); the click is stopped
           here so the row's own link does not navigate as well. -->
      <span
        v-if="showShortcut"
        role="button"
        tabindex="0"
        class="nav-link-shortcut"
        :aria-label="$t(item.shortcut.title || 'Create')"
        @click.stop.prevent="openShortcut"
        @keydown.enter.stop.prevent="openShortcut"
        @keydown.space.stop.prevent="openShortcut"
      >
        <VIcon :icon="item.shortcut.icon || 'tabler-plus'" size="18" />
        <VTooltip activator="parent" location="end">
          {{ $t(item.shortcut.title || "Create") }}
        </VTooltip>
      </span>
    </Component>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-link a {
    display: flex;
    align-items: center;
  }

  // 👉 Shortcut, joined to the row like a select with an add button: it
  // takes the full row height at the right end, is square, keeps the row's
  // rounded corners on the outside, and a thin line divides it from the
  // title. Always visible; at rest a light tint, solid theme colour when
  // hovered.
  .nav-link .nav-link-shortcut {
    // above the row's hover overlay (a ::before layer), so that layer
    // neither tints it nor takes its clicks
    position: relative;
    z-index: 1;
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    align-self: stretch; // full row height
    justify-content: center;
    // border-inline-start: 1px solid
    //   rgba(var(--v-border-color), var(--v-border-opacity));
    // the row's own rounded corners on the outside, square on the inside
    border-end-end-radius: inherit;
    border-end-start-radius: 0;
    border-start-end-radius: inherit;
    border-start-start-radius: 0;
    aspect-ratio: 1;
    // at rest on a plain row: no fill, just the divider and the "+", so it
    // reads as the end of the row rather than a box floating beside it
    background: transparent;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    cursor: pointer;
    // flush with the row's right edge (the link has 0.75rem end padding)
    margin-inline: 8px -0.75rem;
    transition:
      color 0.15s ease,
      background-color 0.15s ease,
      border-color 0.15s ease;

    &:hover {
      border-inline-start-color: rgb(var(--v-theme-primary));
      background: rgb(var(--v-theme-primary));
      color: rgb(var(--v-theme-on-primary));
    }

    &:focus-visible {
      outline: 2px solid rgb(var(--v-theme-primary));
      outline-offset: -2px;
    }
  }

  // row hovered / focused (the row gets its own hover tint): the "+" tints
  // with it in the theme colour, so the two still read as one piece
  .nav-link.has-shortcut:hover > a:not(.router-link-active) .nav-link-shortcut:not(:hover),
  .nav-link.has-shortcut:focus-within > a:not(.router-link-active) .nav-link-shortcut:not(:hover) {
    border-inline-start-color: rgba(var(--v-theme-primary), 0.2);
    background: rgba(var(--v-theme-primary), 0.08);
    color: rgb(var(--v-theme-primary));
  }

  // on the active (filled blue) row: see-through white with a light divider,
  // so it stays part of the blue row
  .nav-link.has-shortcut > a.router-link-active .nav-link-shortcut {
    border-inline-start-color: rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.16);
    color: rgb(var(--v-theme-on-primary));

    &:hover {
      background: rgba(255, 255, 255, 0.3);
    }
  }
}
</style>
