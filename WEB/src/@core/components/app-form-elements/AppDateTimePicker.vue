<script setup>
import FlatPickr from "vue-flatpickr-component";
import { useTheme } from "vuetify";
import { VField, makeVFieldProps } from "vuetify/lib/components/VField/VField";
import { VInput, makeVInputProps } from "vuetify/lib/components/VInput/VInput";

import { filterInputAttrs } from "vuetify/lib/util/helpers";
import { useConfigStore } from "@core/stores/config";
import { Khmer } from "flatpickr/dist/l10n/km.js";

const props = defineProps({
  autofocus: Boolean,
  counter: [Boolean, Number, String],
  counterValue: Function,
  prefix: String,
  placeholder: String,
  persistentPlaceholder: Boolean,
  persistentCounter: Boolean,
  suffix: String,
  type: {
    type: String,
    default: "text",
  },
  modelModifiers: Object,
  locale: {
    type: String,
    default: "default", // 'default', 'km' for Khmer
  },
  ...makeVInputProps({
    density: "comfortable",
    hideDetails: "auto",
  }),
  ...makeVFieldProps({
    variant: "outlined",
    color: "primary",
  }),
});

const emit = defineEmits([
  "click:control",
  "mousedown:control",
  "update:focused",
  "update:modelValue",
  "click:clear",
]);

defineOptions({
  inheritAttrs: false,
});

const configStore = useConfigStore();
const attrs = useAttrs();
const [rootAttrs, compAttrs] = filterInputAttrs(attrs);
const inputProps = ref(VInput.filterProps(props));
const fieldProps = ref(VField.filterProps(props));
const refFlatPicker = ref();
const { focused } = useFocus(refFlatPicker);
const isCalendarOpen = ref(false);
const isInlinePicker = ref(false);

// flat picker prop manipulation
if (compAttrs.config && compAttrs.config.inline) {
  isInlinePicker.value = compAttrs.config.inline;
  Object.assign(compAttrs, { altInputClass: "inlinePicker" });
}

// 👇 FIXED: Added disableMobile and input mode numeric
compAttrs.config = {
  ...compAttrs.config,

  disableMobile: true,

  // 👇 VALUE (v-model)
  dateFormat: "Y-m-d",

  // 👇 DISPLAY (input)
  altInput: true,
  altFormat: "d-m-Y",

  locale: {
    ...Khmer,
    firstDayOfWeek: 1,
  },

  prevArrow:
    '<i class="tabler-chevron-left v-icon" style="font-size: 20px;"></i>',
  nextArrow:
    '<i class="tabler-chevron-right v-icon" style="font-size: 20px;"></i>',
};

const onClear = (el) => {
  el.stopPropagation();
  nextTick(() => {
    emit("update:modelValue", "");
    emit("click:clear", el);
  });
};

const vuetifyTheme = useTheme();
const vuetifyThemesName = Object.keys(vuetifyTheme.themes.value);

// 👇 FIXED: Added optional chaining for better mobile handling
const updateThemeClassInCalendar = () => {
  // Check if flatpickr instance exists and has calendar container
  if (!refFlatPicker.value?.fp?.calendarContainer) {
    return;
  }

  vuetifyThemesName.forEach((t) => {
    refFlatPicker.value.fp.calendarContainer.classList.remove(`v-theme--${t}`);
  });
  refFlatPicker.value.fp.calendarContainer.classList.add(
    `v-theme--${vuetifyTheme.global.name.value}`,
  );
};

// 👇 NEW: Auto-format date input
const formatDateInput = (value) => {
  // Remove all non-numeric characters
  const numbers = value.replace(/\D/g, "");

  // Format based on length
  if (numbers.length <= 4) {
    // YYYY
    return numbers;
  } else if (numbers.length <= 6) {
    // YYYY-MM
    return `${numbers.slice(0, 4)}-${numbers.slice(4)}`;
  } else {
    // YYYY-MM-DD
    return `${numbers.slice(0, 4)}-${numbers.slice(4, 6)}-${numbers.slice(
      6,
      8,
    )}`;
  }
};

// 👇 NEW: Handle keyboard input
const handleInput = (event) => {
  const input = event.target;
  const cursorPosition = input.selectionStart;
  const oldValue = input.value;
  const oldLength = oldValue.length;

  // Get only numbers from input
  const numbers = input.value.replace(/\D/g, "");

  // Limit to 8 digits (YYYYMMDD)
  const limitedNumbers = numbers.slice(0, 8);

  // Format the date
  const formatted = formatDateInput(limitedNumbers);

  // Update input value
  input.value = formatted;

  // Adjust cursor position
  const newLength = formatted.length;
  let newPosition = cursorPosition + (newLength - oldLength);

  // If cursor is after a dash that was just added, move it forward
  if (
    formatted[newPosition - 1] === "-" &&
    oldValue[cursorPosition - 1] !== "-"
  ) {
    newPosition++;
  }

  // Set cursor position
  input.setSelectionRange(newPosition, newPosition);

  // Update model value if complete date
  if (limitedNumbers.length === 8) {
    emit("update:modelValue", formatted);
  } else if (limitedNumbers.length === 6) {
    // Also emit for YYYY-MM format
    emit("update:modelValue", formatted);
  } else if (limitedNumbers.length === 4) {
    // Also emit for YYYY format
    emit("update:modelValue", formatted);
  }
};

// 👇 NEW: Handle keydown to prevent non-numeric input
const handleKeyDown = (event) => {
  // Allow: backspace, delete, tab, escape, enter, dash
  if (
    [8, 9, 27, 13, 46, 189, 109].includes(event.keyCode) ||
    // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
    (event.keyCode === 65 && event.ctrlKey === true) ||
    (event.keyCode === 67 && event.ctrlKey === true) ||
    (event.keyCode === 86 && event.ctrlKey === true) ||
    (event.keyCode === 88 && event.ctrlKey === true) ||
    // Allow: home, end, left, right
    (event.keyCode >= 35 && event.keyCode <= 39)
  ) {
    return;
  }

  // Ensure that it is a number and stop the keypress
  if (
    (event.shiftKey || event.keyCode < 48 || event.keyCode > 57) &&
    (event.keyCode < 96 || event.keyCode > 105)
  ) {
    event.preventDefault();
  }
};

watch(() => configStore.theme, updateThemeClassInCalendar);
onMounted(() => {
  updateThemeClassInCalendar();

  nextTick(() => {
    const altInput = refFlatPicker.value?.fp?.altInput;
    if (!altInput) return;

    altInput.setAttribute("inputmode", "numeric");

    altInput.addEventListener("input", (e) => {
      let v = e.target.value.replace(/\D/g, "").slice(0, 8);

      if (v.length >= 5) v = `${v.slice(0, 2)}-${v.slice(2, 4)}-${v.slice(4)}`;
      else if (v.length >= 3) v = `${v.slice(0, 2)}-${v.slice(2)}`;

      e.target.value = v;
    });
  });
});

onBeforeUnmount(() => {
  // Clean up event listeners
  if (refFlatPicker.value?.fp?.input) {
    const input = refFlatPicker.value.fp.input;
    input.removeEventListener("input", handleInput);
    input.removeEventListener("keydown", handleKeyDown);
  }
});

const emitModelValue = (val) => {
  emit("update:modelValue", val);
};

watch(
  () => props,
  () => {
    fieldProps.value = VField.filterProps(props);
    inputProps.value = VInput.filterProps(props);
  },
  {
    deep: true,
    immediate: true,
  },
);

const elementId = computed(() => {
  const _elementIdToken =
    fieldProps.value.id || fieldProps.value.label || inputProps.value.id;
  const _id = useId();

  return _elementIdToken ? `app-picker-field-${_elementIdToken}` : _id;
});
</script>

<template>
  <div class="app-picker-field">
    <VLabel
      v-if="fieldProps.label"
      class="mb-1 text-wrap battambang font-size-0-75 pt-2"
      style="line-height: 15px"
      :for="elementId"
      :text="$t(fieldProps.label)"
    />

    <VInput
      v-bind="{ ...inputProps, ...rootAttrs }"
      :model-value="modelValue"
      :class="[
        {
          'v-text-field--prefixed': props.prefix,
          'v-text-field--suffixed': props.suffix,
          'v-text-field--flush-details': ['plain', 'underlined'].includes(
            props.variant,
          ),
        },
        props.class,
      ]"
      class="position-relative v-text-field"
      :style="props.style"
    >
      <template
        #default="{ id, isDirty, isValid, isDisabled, isReadonly, validate }"
      >
        <VField
          v-bind="{ ...fieldProps, label: undefined }"
          :id="id.value"
          role="textbox"
          :active="focused || isDirty.value || isCalendarOpen"
          :focused="focused || isCalendarOpen"
          :dirty="isDirty.value || props.dirty"
          :error="isValid.value === false"
          :disabled="isDisabled.value"
          clear-icon="tabler-x"
          prepend-inner-icon="tabler-calendar-due"
          @click:clear="onClear"
        >
          <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps || {}" />
          </template>
          <template #default="{ props: vFieldProps }">
            <div v-bind="vFieldProps">
              <FlatPickr
                v-if="!isInlinePicker"
                v-bind="compAttrs"
                ref="refFlatPicker"
                :model-value="modelValue"
                :readonly="isReadonly.value"
                placeholder="DD-MM-YYYY"
                class="flat-picker-custom-style h-100 w-100"
                :disabled="isReadonly.value"
                inputmode="numeric"
                @on-open="isCalendarOpen = true"
                @on-close="
                  isCalendarOpen = false;
                  validate();
                "
                @update:model-value="emitModelValue"
              />

              <input
                v-if="isInlinePicker"
                :value="modelValue"
                :placeholder="props.placeholder"
                :readonly="isReadonly.value"
                class="flat-picker-custom-style h-100 w-100"
                type="text"
                inputmode="numeric"
              />
            </div>
          </template>
        </VField>
      </template>
    </VInput>

    <FlatPickr
      v-if="isInlinePicker"
      v-bind="compAttrs"
      ref="refFlatPicker"
      :model-value="modelValue"
      @update:model-value="emitModelValue"
      @on-open="isCalendarOpen = true"
      @on-close="isCalendarOpen = false"
    />
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/mixins" as templateMixins;
@use "@core/scss/base/mixins";
@use "flatpickr/dist/flatpickr.css";

/* stylelint-disable no-descending-specificity */

// ── tokens ────────────────────────────────────────────────────
$fp-width: 20rem;
$fp-gutter: 0.5rem;
$fp-cell: calc((#{$fp-width} - (#{$fp-gutter} * 2)) / 7);
$fp-day-h: 2.625rem;

$fp-heading: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
$fp-body: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
$fp-muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$fp-hairline: rgba(var(--v-border-color), var(--v-border-opacity));
$fp-hover: rgba(var(--v-theme-on-surface), 6%);
$fp-font: "inter", "kantumruy", "inter", sans-serif;

// ── the field itself ──────────────────────────────────────────
.app-picker-field {
  .flat-picker-custom-style {
    position: absolute;
    color: inherit;
    font-family: $fp-font;
    font-variant-numeric: tabular-nums;
    inline-size: 100%;
    inset: 0;
    letter-spacing: 0.01em;
    outline: none;
    padding-block: 0;
    padding-inline: var(--v-field-padding-start);

    &::placeholder {
      color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
    }
  }

  // calendar icon reacts to focus instead of sitting there inert
  .v-field__prepend-inner .v-icon {
    opacity: 1;
    transition:
      color 0.2s ease,
      transform 0.2s ease;
  }

  .v-field--focused .v-field__prepend-inner .v-icon {
    color: rgb(var(--v-theme-primary));
    transform: scale(1.06);
  }
}

// hide the input when the picker is inline
input[altinputclass="inlinePicker"] {
  display: none;
}

// ── calendar shell ────────────────────────────────────────────
.flatpickr-calendar {
  overflow: hidden;
  border: 1px solid $fp-hairline;
  border-radius: 6px;
  background-color: rgb(var(--v-theme-surface));
  box-shadow:
    0 2px 6px rgb(0 0 0 / 6%),
    0 12px 32px -8px rgb(0 0 0 / 18%);
  font-family: $fp-font;
  inline-size: $fp-width;
  padding-block-end: 0.25rem;

  &::after,
  &::before {
    display: none; // kill the default arrow nub
  }

  &.open {
    z-index: 2401; // above Vuetify overlays
  }

  &.open:not(.inline) {
    animation: app-picker-in 0.16s cubic-bezier(0.2, 0, 0, 1);
  }

  &.inline {
    box-shadow: none;
  }

  // ── header: month + year ───────────────────────────────────
  .flatpickr-months {
    padding-block: 0.875rem 0.5rem;
    padding-inline: 1rem;

    .flatpickr-month {
      display: flex;
      align-items: center;
      block-size: 2.125rem;
      color: $fp-heading;
    }

    .flatpickr-current-month {
      display: flex;
      align-items: center;
      padding: 0;
      block-size: 1.75rem;
      gap: 0.25rem;
      inset-inline-start: 0;
      text-align: start;
    }

    .flatpickr-prev-month,
    .flatpickr-next-month {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      border-radius: 50%;
      background: transparent;
      block-size: 1.875rem;
      color: $fp-muted;
      fill: currentcolor;
      inline-size: 1.875rem;
      inset-block-start: 13px !important;
      transition:
        background-color 0.15s ease,
        color 0.15s ease;

      &:hover {
        background: $fp-hover;
        color: rgb(var(--v-theme-primary));
      }

      &:active {
        background: rgba(var(--v-theme-primary), 16%);
      }

      &:hover i,
      &:hover svg {
        fill: currentcolor;
      }

      &.flatpickr-disabled {
        display: inline;
        opacity: var(--v-disabled-opacity);
        pointer-events: none;
      }
    }

    .flatpickr-next-month {
      inset-inline-end: 0.75rem !important;
    }

    .flatpickr-prev-month {
      /* stylelint-disable-next-line liberty/use-logical-spec */
      right: 3.35rem;
      left: unset !important;
    }
  }

  .flatpickr-current-month {
    font-size: 1rem;

    span.cur-month {
      margin: 0;
      color: $fp-heading;
      font-weight: 600;
      letter-spacing: -0.01em;
    }

    .flatpickr-monthDropdown-months,
    .numInputWrapper {
      padding-block: 0.125rem;
      padding-inline: 0.375rem;
      border-radius: 8px;
      color: $fp-heading;
      font-size: 0.9375rem;
      font-weight: 600;
      line-height: 1.375rem;
      transition: background-color 0.15s ease;

      &:hover {
        background: $fp-hover;
      }

      span {
        display: none; // no spinner arrows
      }

      .flatpickr-monthDropdown-month {
        background-color: rgb(var(--v-theme-surface));
        color: $fp-body;
        font-weight: 400;
      }
    }

    .flatpickr-monthDropdown-months {
      appearance: none;
      border: none;
      cursor: pointer;
      outline: none;
    }

    .numInput.cur-year {
      color: $fp-muted;
      font-variant-numeric: tabular-nums;
      font-weight: 500;
    }
  }

  // ── weekday row ────────────────────────────────────────────
  .flatpickr-weekdays {
    margin-block: 0.25rem 0.125rem;
    block-size: 1.75rem;
    padding-inline: $fp-gutter;
  }

  .flatpickr-weekday {
    color: $fp-muted;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    line-height: 1.75rem;
    text-transform: uppercase;
  }

  // ── day grid ───────────────────────────────────────────────
  .flatpickr-rContainer,
  .flatpickr-days,
  .dayContainer {
    box-sizing: border-box;
    inline-size: $fp-width;
    max-inline-size: $fp-width;
    min-inline-size: $fp-width;
  }

  .dayContainer {
    justify-content: flex-start !important;
    padding-block: 0.25rem 0.75rem;
    padding-inline: $fp-gutter;
  }

  .flatpickr-day {
    position: relative;
    border: none;
    border-radius: 10%;
    block-size: $fp-day-h;
    color: $fp-body;
    font-size: 0.875rem;
    font-variant-numeric: tabular-nums;
    font-weight: 450;
    inline-size: $fp-cell;
    line-height: $fp-day-h;
    margin-block-start: 0 !important;
    max-inline-size: $fp-cell;
    transition:
      background-color 0.15s ease,
      color 0.15s ease,
      box-shadow 0.15s ease;

    &:hover {
      border-color: transparent;
      background: $fp-hover;
    }

    // today = ring, never a fill, so it survives next to a selection
    &.today:not(.selected, .inRange) {
      background: transparent;
      box-shadow: inset 0 0 0 1.5px rgb(var(--v-theme-primary));
      color: rgb(var(--v-theme-primary));
      font-weight: 600;

      &:hover {
        background: rgba(var(--v-theme-primary), 10%);
      }
    }

    // when today is also the selection, keep a marker dot
    &.today.selected::after {
      position: absolute;
      border-radius: 50%;
      background: rgb(var(--v-theme-on-primary));
      block-size: 3px;
      content: "";
      inline-size: 3px;
      inset-block-end: 0.375rem;
      inset-inline-start: 50%;
      opacity: 0.9;
      transform: translateX(-50%);
    }

    &.selected,
    &.selected:hover {
      background: rgb(var(--v-theme-primary));
      box-shadow: 0 3px 10px -2px rgba(var(--v-theme-primary), 50%);
      color: rgb(var(--v-theme-on-primary));
      font-weight: 600;
    }

    // ── range: one continuous band ───────────────────────────
    &.inRange,
    &.inRange:hover {
      border-radius: 0;
      background: rgba(var(--v-theme-primary), 12%) !important;
      box-shadow: none !important;
      color: rgb(var(--v-theme-primary));
      font-weight: 500;
    }

    &.startRange,
    &.startRange:hover {
      border-end-end-radius: 0;
      border-start-end-radius: 0;
    }

    &.endRange,
    &.endRange:hover {
      border-end-start-radius: 0;
      border-start-start-radius: 0;
    }

    &.startRange.endRange {
      border-radius: 50%;
    }

    &.startRange,
    &.endRange {
      background: rgb(var(--v-theme-primary));
      box-shadow: 0 3px 10px -2px rgba(var(--v-theme-primary), 45%);
      color: rgb(var(--v-theme-on-primary));
      font-weight: 600;
    }

    &.flatpickr-disabled,
    &.flatpickr-disabled:hover {
      background: transparent;
      color: $fp-body;
      opacity: var(--v-disabled-opacity);
    }

    &.prevMonthDay:not(.startRange, .inRange, .endRange),
    &.nextMonthDay:not(.startRange, .inRange, .endRange) {
      color: $fp-muted;
      opacity: 0.45;
    }
  }

  // ── time picker ────────────────────────────────────────────
  &.hasTime.open {
    .flatpickr-innerContainer + .flatpickr-time {
      block-size: auto;
      border-block-start: 1px solid $fp-hairline;
    }

    .flatpickr-time {
      border-block-start: none;
    }
  }

  .flatpickr-time {
    padding-block: 0.25rem;

    .flatpickr-am-pm,
    .flatpickr-time-separator,
    input {
      border-radius: 8px;
      color: $fp-body;
      font-size: 0.9375rem;
      font-variant-numeric: tabular-nums;
      font-weight: 500;
      transition:
        background-color 0.15s ease,
        color 0.15s ease;
    }

    input.flatpickr-hour {
      font-weight: 500;
    }

    input:hover,
    input:focus,
    .flatpickr-am-pm:hover,
    .flatpickr-am-pm:focus {
      background: $fp-hover;
      color: rgb(var(--v-theme-primary));
    }

    .flatpickr-time-separator {
      color: $fp-muted;
    }

    .numInputWrapper span {
      &.arrowUp::after {
        border-block-end-color: rgb(var(--v-border-color));
      }

      &.arrowDown::after {
        border-block-start-color: rgb(var(--v-border-color));
      }
    }
  }
}

// ── dark theme: lift the surface, deepen the shadow ───────────
.v-theme--dark .flatpickr-calendar {
  background-image: linear-gradient(
    rgb(255 255 255 / 3%),
    rgb(255 255 255 / 3%)
  );
  box-shadow: 0 12px 34px -6px rgb(15 20 34 / 55%);
}

// flatpickr input is readonly by default — don't let it go grey
.flatpickr-input[readonly],
.flatpickr-input ~ .form-control[readonly],
.flatpickr-human-friendly[readonly] {
  background-color: inherit;
}

@keyframes app-picker-in {
  from {
    opacity: 0;
    transform: translateY(-6px) scale(0.985);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .flatpickr-calendar,
  .flatpickr-calendar * {
    animation: none !important;
    transition: none !important;
  }
}
</style>
