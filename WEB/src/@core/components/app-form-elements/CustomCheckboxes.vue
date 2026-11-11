<script setup>
const props = defineProps({
  selectedCheckbox: {
    type: Array,
    required: true,
  },
  checkboxContent: {
    type: Array,
    required: true,
  },
  gridColumn: {
    type: null,
    required: false,
  },
});

const emit = defineEmits(["update:selectedCheckbox"]);

const updateSelectedOption = (value) => {
  if (typeof value !== "boolean" && value !== null)
    emit("update:selectedCheckbox", value);
};
</script>

<template>
  <VRow
    v-if="props.checkboxContent && props.selectedCheckbox"
    class="custom-input-wrapper"
  >
    <VCol
      v-for="item in props.checkboxContent"
      :key="item.title"
      v-bind="gridColumn"
    >
      <VLabel
        class="custom-input custom-checkbox rounded cursor-pointer"
        :style="
          props.selectedCheckbox.includes(item.value) && item.color
            ? `border-color: rgb(var(--v-theme-${item?.color || 'primary'})) !important;  border-style: solid !important;`
            : ''
        "
      >
        <div>
          <VCheckbox
            :model-value="props.selectedCheckbox"
            :value="item.value"
            :color="item.color"
            :base-color="item.color"
            @update:model-value="updateSelectedOption"
          />
        </div>
        <slot :item="item">
          <div class="flex-grow-1">
            <div class="d-flex align-center mb-2">
              <h6
                class="cr-title text-base"
                :style="
                  props.selectedCheckbox.includes(item.value) && item.color
                    ? `color: rgb(var(--v-theme-${item.color}));`
                    : 'color: rgba(var(--v-theme-on-background), var(--v-disabled-opacity));'
                "
              >
                {{ item.title }}
              </h6>
              <VSpacer />
              <span v-if="item.subtitle" class="text-disabled text-body-2">{{
                item.subtitle
              }}</span>
            </div>
            <p
              class="text-sm mb-0"
              :style="
                props.selectedCheckbox.includes(item.value) && item.color
                  ? `color: rgb(var(--v-theme-${item.color}));`
                  : 'color: rgba(var(--v-theme-on-background), var(--v-disabled-opacity));'
              "
            >
              {{ item.desc }}
            </p>
          </div>
        </slot>
      </VLabel>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.custom-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;

  .v-checkbox {
    margin-block-start: -0.375rem;
  }

  .cr-title {
    font-weight: 500;
    line-height: 1.375rem;
    transition: color 0.2s ease;
  }

  // Force checkbox icon color to follow the color prop
  :deep(.v-checkbox .v-selection-control__input .v-icon) {
    color: inherit;
  }

  :deep(.v-checkbox .v-selection-control--dirty) {
    color: inherit;
  }
}

.custom-input {
  transition:
    border-color 0.2s ease,
    border-width 0.2s ease;
}
</style>
