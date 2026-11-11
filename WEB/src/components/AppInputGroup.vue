<script setup>
import { computed } from "vue";
import { useDisplay } from "vuetify";

/*
  Joins the inputs placed inside it into one field: a shared border, only the
  outer corners rounded, the focused input drawn on top of its neighbours.

    <AppInputGroup stack-on="xs" :widths="['25%', null]">
      <AppAutocomplete v-model="typeId" ... />
      <AppAutocomplete v-model="accountCode" ... />
    </AppInputGroup>

  Widths per screen size: give one list per Vuetify breakpoint; each size
  uses its own list, or the nearest smaller size that has one:

    <AppInputGroup :widths="{ xs: ['40%', null], lg: ['25%', null] }">

  Without `widths` each input takes an equal share. Works with any
  Vuetify-based input (AppTextField, AppSelect, AppAutocomplete,
  AppDateTimePicker, ...).
*/
// how many inputs `widths` can size (the CSS below has one rule per slot)
const MAX_SIZED = 6;
const BREAKPOINTS = ["xs", "sm", "md", "lg", "xl", "xxl"];

const props = defineProps({
  // width of each input, in order: a number (px), any CSS width ("25%",
  // "180px", "clamp(120px, 28%, 200px)") or null to take the rest.
  // Or an object of such lists per screen size: { xs: [...], lg: [...] }
  widths: {
    type: [Array, Object],
    default: () => [],
  },
  // screen size at and below which the inputs stack top to bottom
  // ("xs", "sm", "md", "lg"); none: always side by side
  stackOn: {
    type: String,
    default: null,
    validator: (v) => [null, "xs", "sm", "md", "lg"].includes(v),
  },
});

const display = useDisplay();

// The width list for the current screen: the list itself, or for a
// per-size object the entry of this size or the nearest smaller one
const activeWidths = computed(() => {
  if (Array.isArray(props.widths)) return props.widths;

  const current = BREAKPOINTS.indexOf(display.name.value);
  for (let i = current; i >= 0; i--) {
    const list = props.widths[BREAKPOINTS[i]];
    if (Array.isArray(list)) return list;
  }
  return []; // nothing set this small: equal shares
});

// Each width becomes a CSS variable the matching :nth-child rule reads,
// since the inputs come from the slot and can't be given a style here
const widthVars = computed(() => {
  const vars = {};
  activeWidths.value.slice(0, MAX_SIZED).forEach((width, index) => {
    if (width === null || width === undefined || width === "") return;
    // a bare number (75 or "75") means px; anything else is used as given
    const size = /^\d+(\.\d+)?$/.test(String(width).trim())
      ? `${String(width).trim()}px`
      : width;
    vars[`--app-input-group-w${index + 1}`] = `0 0 ${size}`;
  });
  return vars;
});

const isStacked = computed(() => {
  if (!props.stackOn) return false;
  // Vuetify has no "xsAndDown": xs is already the smallest
  return props.stackOn === "xs"
    ? display.xs.value
    : display[`${props.stackOn}AndDown`].value;
});
</script>

<template>
  <div
    class="app-input-group"
    :class="{ 'app-input-group--stacked': isStacked }"
    :style="widthVars"
  >
    <slot />
  </div>
</template>

<!-- Not scoped: the rules style the slotted inputs and the Vuetify field
     inside them, which a scoped selector can't reach. Every selector starts
     with .app-input-group, so nothing outside a group is affected. -->
<style>
.app-input-group {
  --app-input-group-radius: 6px;
  display: flex;
  align-items: flex-start;
}

.app-input-group > * {
  flex: 1 1 0;
  min-inline-size: 0;
}

/* `widths`: the input in position n reads --app-input-group-wn; unset
   means the default above (take an equal share of what is left) */
.app-input-group > :nth-child(1) { flex: var(--app-input-group-w1, 1 1 0); }
.app-input-group > :nth-child(2) { flex: var(--app-input-group-w2, 1 1 0); }
.app-input-group > :nth-child(3) { flex: var(--app-input-group-w3, 1 1 0); }
.app-input-group > :nth-child(4) { flex: var(--app-input-group-w4, 1 1 0); }
.app-input-group > :nth-child(5) { flex: var(--app-input-group-w5, 1 1 0); }
.app-input-group > :nth-child(6) { flex: var(--app-input-group-w6, 1 1 0); }

/* overlap the borders so each joint is one line, not two */
.app-input-group > * + * {
  margin-inline-start: -1px;
}

/* side by side: square the inner corners */
.app-input-group > *:not(:first-child) .v-field {
  border-start-start-radius: 0;
  border-end-start-radius: 0;
}
.app-input-group > *:not(:last-child) .v-field {
  border-start-end-radius: 0;
  border-end-end-radius: 0;
}

/* the focused / errored input draws its border over its neighbour's */
.app-input-group .v-field--focused,
.app-input-group .v-field--error {
  z-index: 1;
}

/* stacked: top to bottom, only the top and bottom corners rounded */
.app-input-group--stacked {
  flex-direction: column;
  align-items: stretch;
}
.app-input-group--stacked > * {
  flex: 0 0 auto !important; /* a side-by-side width no longer applies */
  max-inline-size: none !important;
}
.app-input-group--stacked > * + * {
  margin-inline-start: 0;
  margin-block-start: -1px;
}
/* A floating label sits on the field's top border, which here is the joint
   shared with the input above: draw each input over the one before it and
   give its label a backing, so the label hides the line instead of being
   struck through by it */
.app-input-group--stacked > * {
  position: relative;
}
.app-input-group--stacked > * + * {
  z-index: 1;
}
.app-input-group--stacked > * + * .v-field-label--floating {
  padding-inline: 4px;
  margin-inline-start: -4px;
  background: rgb(var(--v-theme-surface));
}
.app-input-group--stacked > * .v-field {
  border-radius: var(--app-input-group-radius);
}
.app-input-group--stacked > *:not(:first-child) .v-field {
  border-start-start-radius: 0;
  border-start-end-radius: 0;
}
.app-input-group--stacked > *:not(:last-child) .v-field {
  border-end-start-radius: 0;
  border-end-end-radius: 0;
}
</style>
