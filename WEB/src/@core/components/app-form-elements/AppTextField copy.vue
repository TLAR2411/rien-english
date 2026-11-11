<script setup>
import { computed, ref, watch, useAttrs, useId } from "vue";

defineOptions({
  name: "AppTextField",
  inheritAttrs: false,
});

const props = defineProps({
  formatCurrency: {
    type: Boolean,
    default: false,
  },
  numbersOnly: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    default: "",
  },
  variant: {
    type: String,
    default: "outlined",
  },
  min: {
    type: [String, Number],
    default: null,
  },
  max: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(["update:modelValue"]);
const attrs = useAttrs();

const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id;
  const _id = useId();
  return _elementIdToken ? `app-text-field-${_elementIdToken}` : _id;
});

const label = computed(() => useAttrs().label);
const displayValue = ref("");

// Helper: Format number with commas
const formatCurrency = (value) => {
  if (value === "" || value === null || value === undefined) return "0";
  const numericValue = String(value).replace(/[^0-9.]/g, "");
  const [whole, decimal] = numericValue.split(".");
  const formattedWhole = new Intl.NumberFormat("en-US").format(
    Number(whole || 0),
  );
  if (decimal !== undefined) {
    return `${formattedWhole}.${decimal.slice(0, 2)}`;
  }
  return formattedWhole;
};

// Helper: Parse string back to number
const parseCurrency = (value) => {
  if (!value) return 0;
  const stringValue = String(value);
  const numericString = stringValue.replace(/[^0-9.]/g, "");
  const number = parseFloat(numericString);
  return isNaN(number) ? 0 : number;
};

const validateNumbersOnly = (value) => {
  return value.replace(/[^0-9]/g, "");
};

const validateCurrency = (value) => {
  return value.replace(/[^0-9.]/g, "").replace(/(\..*?)\./g, "$1");
};

// 👉 UPDATED HANDLE INPUT (Allows empty)
const handleInput = (event) => {
  let inputValue = event.target.value;

  if (props.numbersOnly) {
    inputValue = validateNumbersOnly(inputValue);
    displayValue.value = inputValue;
    emit("update:modelValue", inputValue);
  } else if (props.formatCurrency) {
    // 1. Validate chars
    inputValue = validateCurrency(inputValue);

    // 2. Allow empty string (Do NOT force '0' here)
    displayValue.value = inputValue;

    // 3. If empty, emit 0 so math works. If number, parse it.
    if (inputValue === "") {
      emit("update:modelValue", 0);
    } else {
      const numericValue = parseCurrency(inputValue);
      emit("update:modelValue", numericValue);
    }
  } else {
    displayValue.value = inputValue;
    emit("update:modelValue", inputValue);
  }
};

// 👉 UPDATED WATCHER (Stops the "Stuck 0" loop)
watch(
  () => props.modelValue,
  (newValue) => {
    // 1. If we are in "Numbers Only" mode
    if (props.numbersOnly) {
      displayValue.value = validateNumbersOnly(String(newValue || ""));
      return;
    }

    // 2. If we are in "Currency" mode
    if (props.formatCurrency) {
      // SPECIAL CHECK:
      // If the new value is 0, AND the current input is empty (user just deleted it),
      // STOP here. Do not force "0" back into the box.
      if ((newValue === 0 || newValue === "0") && displayValue.value === "") {
        return;
      }

      // Otherwise, format normally
      if (newValue === 0 || newValue === "0") {
        displayValue.value = "0";
      } else {
        displayValue.value = formatCurrency(newValue || "");
      }
    } else {
      // Default mode
      displayValue.value = newValue || "";
    }
  },
  { immediate: true },
);

// 👉 UPDATED HANDLE BLUR (Snaps to 0 when leaving)
const handleBlur = () => {
  if (props.numbersOnly) {
    displayValue.value = validateNumbersOnly(displayValue.value);
  } else if (props.formatCurrency) {
    // If field is empty when user clicks away, NOW we force it to "0"
    if (!displayValue.value || displayValue.value === "") {
      displayValue.value = "0";
      emit("update:modelValue", 0); // Ensure parent has 0
    } else {
      // Otherwise just re-format (e.g. 1000 -> 1,000)
      displayValue.value = formatCurrency(displayValue.value);
    }
  }
};

const handleKeypress = (event) => {
  const char = String.fromCharCode(event.keyCode || event.which);
  const currentValue = event.target.value;

  if (props.numbersOnly) {
    if (!/[0-9]/.test(char)) event.preventDefault();
  } else if (props.formatCurrency) {
    if (!/[0-9.]/.test(char) || (char === "." && currentValue.includes("."))) {
      event.preventDefault();
    }
  }
};

watch(
  () => props.modelValue,
  (newValue) => {
    if (props.numbersOnly) {
      displayValue.value = validateNumbersOnly(String(newValue || ""));
    } else if (props.formatCurrency) {
      if (newValue === 0 || newValue === "0") {
        displayValue.value = "0";
      } else {
        displayValue.value = formatCurrency(newValue || "");
      }
    } else {
      displayValue.value = newValue || "";
    }
  },
  { immediate: true },
);

const internalRules = computed(() => {
  const rules = [];
  if (props.min !== null) {
    rules.push((value) => {
      const numericValue = parseFloat(parseCurrency(value));
      return numericValue >= props.min || `មិនអាចតិចជាង:${props.min}`;
    });
  }
  if (props.max !== null) {
    rules.push((value) => {
      const numericValue = parseFloat(parseCurrency(value));
      return (
        numericValue <= props.max || `មិនអាចលើលពី:${formatCurrency(props.max)}`
      );
    });
  }
  return rules;
});

const allRules = computed(() => {
  const parentRules = attrs.rules || [];
  return [...internalRules.value, ...parentRules];
});
</script>

<template>
  <div class="app-text-field flex-grow-1" :class="$attrs.class">
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-wrap notasans font-size-0-75 pt-2"
      style="line-height: 15px"
      :text="$t(label)"
    />
    <VTextField
      v-bind="{
        ...$attrs,
        class: null,
        label: undefined,
        id: elementId,
        rules: undefined,
      }"
      :variant="variant"
      :modelValue="displayValue"
      @input="handleInput"
      @blur="handleBlur"
      @keypress="handleKeypress"
      :pattern="
        numbersOnly ? '[0-9]*' : formatCurrency ? '[0-9]*\\.?[0-9]*' : null
      "
      :rules="allRules"
      autocomplete="off"
      clear-icon="tabler-x"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData || {}" />
      </template>
    </VTextField>
  </div>
</template>
