<script setup>
defineOptions({
  name: "AppSelect",
  inheritAttrs: false,
});

const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id;
  const _id = useId();

  return _elementIdToken ? `app-select-${_elementIdToken}` : _id;
});

const label = computed(() => useAttrs().label);
</script>

<template>
  <div class="app-select flex-grow-1" :class="$attrs.class">
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-wrap notasans font-size-0-75 pt-2"
      style="line-height: 15px"
      :text="$t(label)"
    />
    <VSelect
      v-bind="{
        ...$attrs,
        class: null,
        label: undefined,
        variant: 'outlined',
        id: elementId,
        menuProps: {
          contentClass: [
            'app-inner-list',
            'app-select__content',
            'v-select__content',
            $attrs.multiple !== undefined ? 'v-list-select-multiple' : '',
          ],
        },
      }"
      clear-icon="tabler-x"
      autocomplete="off"
    >
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
    </VSelect>
  </div>
</template>
