<script setup>
// Short list of people (avatar, name, detail, time) with an optional "see all" link.
defineProps({
  title: { type: String, required: true },
  icon: { type: String, required: true },
  color: { type: String, default: "primary" },
  items: { type: Array, default: () => [] }, // [{ key, name, image, title, subtitle, trailing, trailingColor }]
  emptyText: { type: String, default: "No data available" },
  to: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  // Rows become buttons and emit `select` with the item
  clickable: { type: Boolean, default: false },
});

const emit = defineEmits(["select"]);
</script>

<template>
  <VCard class="h-100 d-flex flex-column">
    <VCardItem>
      <template #prepend>
        <VIcon :icon="icon" :color="color" />
      </template>
      <VCardTitle>{{ $t(title) }}</VCardTitle>
      <template v-if="to" #append>
        <VBtn :to="to" variant="text" size="small" append-icon="tabler-chevron-right">
          {{ $t("View All") }}
        </VBtn>
      </template>
    </VCardItem>

    <VCardText class="flex-grow-1 pt-0">
      <VSkeletonLoader v-if="loading" type="list-item-avatar-two-line@4" />

      <div
        v-else-if="!items.length"
        class="d-flex flex-column align-center justify-center text-medium-emphasis py-6 gap-2"
      >
        <VIcon icon="tabler-circle-check" color="success" size="28" />
        {{ $t(emptyText) }}
      </div>

      <VList v-else density="compact" class="py-0">
        <VListItem
          v-for="item in items"
          :key="item.key"
          :class="clickable ? 'px-2 rounded' : 'px-0'"
          :link="clickable"
          @click="clickable && emit('select', item)"
        >
          <template #prepend>
            <AppAvatar
              :image="item.image"
              :title="item.name"
              :size="36"
              :is-show-full-image="false"
              class="me-3"
            />
          </template>
          <VListItemTitle class="text-body-2 font-weight-medium ml-2">
            {{ item.title }}
          </VListItemTitle>
          <VListItemSubtitle class="text-caption text-wrap ml-2">
            {{ item.subtitle }}
          </VListItemSubtitle>
          <template v-if="item.trailing" #append>
            <span
              class="text-caption text-no-wrap ms-2"
              :class="item.trailingColor ? `text-${item.trailingColor}` : 'text-medium-emphasis'"
            >
              {{ item.trailing }}
            </span>
          </template>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>
