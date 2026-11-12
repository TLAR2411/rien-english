<script setup>
import { useDisplay } from "vuetify";

const props = defineProps({
  title: { type: String, default: "" },
  titleIcon: { type: String, default: "tabler-user" },
  isDialogVisible: { type: Boolean, required: true },
  width: { type: [Number, String], default: 650 },
  isFooter: { type: Boolean, default: false },
  isSubmit: { type: Boolean, default: false },
  submitText: { type: String, default: "Submit" },
  submitIcon: { type: String, default: "tabler-check" },
  submitColor: { type: String, default: "success" },
  loading: { type: Boolean, default: false },
  isPadding: { type: Boolean, default: true },
  // opt-in full screen; on phones (xs) the dialog is otherwise full width
  // with its height fitting the content
  fullscreen: { type: Boolean, default: false },
  // on phones (xs): fill the whole screen, height as well as width — for
  // long, read-only dialogs
  fullHeight: { type: Boolean, default: false },
  // keep the footer (submit button) pinned to the bottom while the content
  // scrolls. Unset: pinned on phones / full screen only. true: pinned on
  // every screen size. false: never pinned, it scrolls with the content.
  pinFooter: { type: Boolean, default: null },
});

const { xs } = useDisplay();

const isFullscreen = computed(
  () => props.fullscreen || (xs.value && props.fullHeight),
);

// phone: edge-to-edge width, height still fits the content
const isFullWidth = computed(() => xs.value && !isFullscreen.value);

// edge-to-edge either way: the floating close button would sit on the
// screen edge, so the header carries the close button instead
const isEdgeToEdge = computed(() => xs.value || isFullscreen.value);

const isFooterPinned = computed(() => props.pinFooter ?? isEdgeToEdge.value);

const emit = defineEmits([
  "update:isDialogVisible",
  "onSubmit",
  "update:loading",
]);
const onCloseDialog = () => emit("update:isDialogVisible", false);

const refForm = ref();
const onFormSubmit = () => emit("onSubmit", refForm.value?.validate());

const _loading = ref(false);

const $loading = computed({
  get() {
    return props.loading !== undefined ? props.loading : _loading.value;
  },
  set(value) {
    props.loading !== undefined
      ? emit("update:loading", value)
      : (_loading.value = value);
  },
});
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="(value) => $emit('update:isDialogVisible', value)"
    persistent
    :width="isFullWidth ? '100%' : width"
    :fullscreen="isFullscreen"
    :content-class="isFullWidth ? 'app-dialog--full-width' : undefined"
  >
    <div
      class="dialog-content"
      :class="{
        'dialog-content--edge': isEdgeToEdge,
        'dialog-content--full': isFullscreen,
        'dialog-content--pin-footer': isFooterPinned,
      }"
    >
      <!-- the floating button sits over the card's corner; edge to edge that
           corner is the screen edge, so the header gets its own button -->
      <!-- <DialogCloseBtn v-if="!isEdgeToEdge" @click="onCloseDialog" /> -->
      <VCard :rounded="isEdgeToEdge ? 0 : undefined">
        <VCardItem v-slot:title class="dialog-header" style="padding: 0.5rem">
          <div
            style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              height: 38px;
              gap: 1rem; /* Added gap for spacing */
            "
          >
            <h5>
              <div class="d-flex align-center text-primary">
                <VAvatar
                  :icon="titleIcon"
                  rounded
                  color="primary"
                  variant="tonal"
                />
                <VCardTitle class="ml-2 text-primary">
                  <span class="text-primary">{{ $t(title) }}</span>
                </VCardTitle>
              </div>
            </h5>
            <IconBtn rounded :aria-label="$t('Close')" @click="onCloseDialog">
              <VIcon icon="tabler-x" />
            </IconBtn>
          </div>
        </VCardItem>

        <VDivider />

        <div :class="isPadding ? 'pa-5' : 'pa-0'">
          <VForm ref="refForm" @submit.prevent="onFormSubmit">
            <slot />
          </VForm>
        </div>

        <VDivider v-if="isFooter && !isFooterPinned" />
        <VCardText
          class="dialog-footer d-flex justify-end flex-wrap gap-3"
          style="padding: 12px"
          v-if="isFooter"
        >
          <div class="pa-2 pt-0 pb-0 w-100">
            <slot name="footer">
              <VBtn
                v-if="isSubmit"
                :color="submitColor"
                :loading="loading"
                class="w-100"
                @click="onFormSubmit"
              >
                <VIcon v-if="submitIcon" start :icon="submitIcon" />
                {{ $t(submitText) }}
              </VBtn>
            </slot>
          </div>
        </VCardText>
      </VCard>
    </div>

    <VOverlay
      v-model="$loading"
      contained
      persistent
      scroll-strategy="none"
      class="align-center justify-center"
    >
      <VProgressCircular indeterminate />
    </VOverlay>
  </VDialog>
</template>

<style scoped>
/* edge to edge (phone / full screen): when the content is taller than the
   screen it scrolls between a pinned header (with its close button) and a
   pinned footer (submit / share / print) */
/* dvh follows the visible viewport, so on phones the dialog shrinks above
   the on-screen keyboard and a pinned footer stays visible */
.dialog-content {
  max-block-size: 100vh;
  max-block-size: 100dvh;
  overflow-y: auto;
}

/* full screen: the scrolling wrapper fills the screen and shares the card's
   surface colour, so a short card still reads as one full-height page */
.dialog-content--full {
  flex: 1 1 auto;
  background: rgb(var(--v-theme-surface));
  block-size: 100%;
  max-block-size: none !important;
}

.dialog-content--full > .v-card {
  box-shadow: none !important;
}

.dialog-content--pin-footer .dialog-footer {
  position: sticky;
  z-index: 2;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 -1px 0 rgba(var(--v-border-color), var(--v-border-opacity));
  inset-block-end: 0;
}

/* sticky needs every ancestor up to the scrolling wrapper to let it out;
   VCard clips with overflow:hidden, which would pin the header to the card
   (that never scrolls) instead of the wrapper */
.dialog-content > .v-card {
  overflow: visible;
}

/* the title bar stays pinned at the top of every dialog while its content
   scrolls; the tint is layered over the surface colour so it is opaque and
   scrolled content does not show through */
.dialog-header {
  position: sticky;
  z-index: 3;
  background:
    linear-gradient(rgba(211, 211, 211, 0.2), rgba(211, 211, 211, 0.2)),
    rgb(var(--v-theme-surface)) !important;
  border-start-end-radius: inherit;
  border-start-start-radius: inherit;
  box-shadow: 0 1px 0 rgba(var(--v-border-color), var(--v-border-opacity));
  inset-block-start: 0;
}

/* the floating close button (desktop) overlaps the card's corner; keep it
   above the pinned header */
.dialog-content > .v-dialog-close-btn {
  z-index: 4;
}

/* the footer's bottom corners follow the card's, now it no longer clips */
.dialog-footer {
  border-end-end-radius: inherit;
  border-end-start-radius: inherit;
}

.dialog-bottom-transition-enter-active,
.dialog-bottom-transition-leave-active {
  transition: transform 0.2s ease-in-out;
}
</style>

<style>
/* phone: VDialog keeps a 24px margin around its content; drop it so the
   dialog spans the full width. Not scoped — the overlay content element is
   rendered by VDialog, outside this component's template. */
.v-dialog > .v-overlay__content.app-dialog--full-width {
  margin: 0;
  inline-size: 100% !important;
  max-inline-size: 100% !important;
}
</style>
