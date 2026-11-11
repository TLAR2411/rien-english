<template>
  <!-- ===== Confirmation dialog (single modal) ===== -->
  <VDialog v-model="isOpen" persistent :max-width="transfer ? 400 : 340">
    <VCard class="confirm-card" :class="`confirm-${dialogIcon}`">
      <div class="confirm-inner">
        <div class="confirm-icon-wrap">
          <VIcon :icon="dialogIconMapping[dialogIcon]" size="30" />
        </div>

        <h3
          class="confirm-title"
          style="font-family: inter, kantumruy, sans-serif"
        >
          {{ $t(heading || labelMapping[dialogIcon]) }}
        </h3>
        <p
          v-if="title"
          class="confirm-message"
          style="font-family: inter, kantumruy, sans-serif"
        >
          {{ title }}
        </p>

        <!-- Optional from → to block drawn as a timeline: red dot (money
             leaves), the amount on the rail, green dot (money arrives) -->
        <div
          v-if="transfer"
          class="confirm-transfer"
          style="font-family: inter, kantumruy, sans-serif"
        >
          <div class="confirm-transfer__step confirm-transfer__step--from">
            <span class="confirm-transfer__dot" />
            <span class="confirm-transfer__label">{{ transfer.from?.label }}</span>
            <span class="confirm-transfer__account">
              <span v-if="transfer.from?.code" class="confirm-transfer__code">
                {{ transfer.from.code }}
              </span>
              {{ transfer.from?.name }}
            </span>
          </div>

          <!-- the only arrow: on the rail, beside the amount -->
          <div class="confirm-transfer__step confirm-transfer__step--move">
            <span class="confirm-transfer__arrow">
              <VIcon icon="tabler-arrow-down" size="16" />
            </span>
            <span class="confirm-transfer__amount">{{ transfer.amount }}</span>
          </div>

          <div class="confirm-transfer__step confirm-transfer__step--to">
            <span class="confirm-transfer__dot" />
            <span class="confirm-transfer__label">{{ transfer.to?.label }}</span>
            <span class="confirm-transfer__account">
              <span v-if="transfer.to?.code" class="confirm-transfer__code">
                {{ transfer.to.code }}
              </span>
              {{ transfer.to?.name }}
            </span>
          </div>
        </div>
        <!-- Optional big value (e.g. an amount) to check before confirming -->
        <div
          v-if="highlight !== null && highlight !== ''"
          class="confirm-highlight"
          style="font-family: inter, kantumruy, sans-serif"
        >
          <span v-if="highlightLabel" class="confirm-highlight__label">
            {{ highlightLabel }}
          </span>
          <span class="confirm-highlight__value">{{ highlight }}</span>
        </div>

        <div class="confirm-actions">
          <VBtn
            v-if="isCancle"
            variant="tonal"
            color="secondary"
            class="confirm-btn confirm-btn-cancel"
            size="small"
            @click="onCancel"
          >
            {{ cancelText ? $t(cancelText) : "" }}
          </VBtn>
          <VBtn
            v-if="isConfirm"
            :color="confirmColor"
            variant="flat"
            class="confirm-btn"
            size="small"
            @click="onConfirm"
          >
            {{ confirmText ? $t(confirmText) : "" }}
          </VBtn>
        </div>
      </div>
    </VCard>
  </VDialog>

  <!-- ===== Toasts (stacked, multiple) ===== -->
  <!-- One wrapper at top-center; each toast stacks vertically inside. -->
  <div class="toast-stack">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast-item"
        :class="`toast-${t.icon}`"
      >
        <div class="toast-row">
          <span class="toast-badge">
            <VIcon :icon="iconMapping[t.icon]" size="16" color="#fff" />
          </span>
          <div class="toast-body">
            <p
              class="toast-title"
              style="font-family: inter, kantumruy, sans-serif !important"
            >
              {{ $t(labelMapping[t.icon] || "") }}
            </p>
            <p
              v-if="t.title"
              class="toast-subtitle"
              style="font-family: inter, kantumruy, sans-serif"
            >
              {{ t.title }}
            </p>
          </div>
          <VBtn
            icon="tabler-x"
            size="x-small"
            variant="text"
            class="toast-close"
            @click="removeToast(t.id)"
          />
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";

/* ===== Dialog state (single modal) ===== */
const isOpen = ref(false);
const isCancle = ref(true);
const isConfirm = ref(true);
const title = ref("");
const icon = ref(null);
// Optional heading; defaults to the icon's label ("Warning", "Confirm", ...)
const heading = ref(null);
// Optional big value shown under the message (e.g. the amount being saved)
const highlight = ref(null);
// Small caption above it (e.g. "Loan Amount")
const highlightLabel = ref(null);
// Optional "from → to" block for a movement of money:
// { from: { label, code, name }, to: { label, code, name }, amount }
const transfer = ref(null);
const confirmColor = ref("primary");
let resolvePromise = null;

const cancelText = ref(null);
const confirmText = ref(null);

/* ===== Toast state (array → multiple) ===== */
const toasts = ref([]);
let toastSeq = 0;

const iconMapping = {
  success: "tabler-check",
  error: "tabler-x",
  warning: "tabler-alert-triangle",
  info: "tabler-info-circle",
};

const labelMapping = {
  success: "Success",
  error: "Error",
  warning: "Warning",
  info: "Info",
  delete: "Delete",
  question: "Confirm",
};

const dialogIconMapping = {
  success: "tabler-circle-check-filled",
  error: "tabler-alert-circle-filled",
  warning: "tabler-alert-triangle-filled",
  info: "tabler-info-circle-filled",
  delete: "tabler-trash-filled",
  question: "tabler-help-circle-filled",
};

// Unknown or missing icon: show the question mark instead of an empty ring
const dialogIcon = computed(() =>
  dialogIconMapping[icon.value] ? icon.value : "question",
);

/* ===== Toast API ===== */
const addToast = (toastTitle, toastIcon, delay) => {
  const id = ++toastSeq;
  const base = delay > 0 ? delay : 3000;
  // small stagger based on stack position so simultaneous toasts don't all
  // expire on the same frame
  const stagger = toasts.value.length * 150;
  const duration = base + stagger;

  const timerId = setTimeout(() => removeToast(id), duration);
  toasts.value.push({ id, title: toastTitle, icon: toastIcon, timerId });
};

const removeToast = (id) => {
  const t = toasts.value.find((t) => t.id === id);
  if (t && t.timerId) {
    clearTimeout(t.timerId); // cancel the pending auto-dismiss
  }
  toasts.value = toasts.value.filter((t) => t.id !== id);
};

/* ===== Main entry point =====
   Accepts EITHER an options object:
     openDialog({ title, icon, isConfirm, timer, ... })
   OR legacy positional args:
     openDialog(title, icon, isCancel, isConfirm, confirmColor, timer, cancelText, confirmText)
   timer > 0  → show a toast (can stack many at once)
   timer == 0 → show the confirmation dialog (single, returns a Promise) */
const openDialog = (arg = {}, ...rest) => {
  // Normalize: if first arg is an object, use it; otherwise treat args as positional.
  let opts;
  if (arg && typeof arg === "object" && !Array.isArray(arg)) {
    opts = arg;
  } else {
    opts = {
      title: arg,
      icon: rest[0],
      heading: null,
      highlight: null,
      highlightLabel: null,
      transfer: null,
      isCancel: rest[1],
      isConfirm: rest[2],
      confirmColor: rest[3],
      timer: rest[4],
      cancelText: rest[5],
      confirmText: rest[6],
    };
  }

  const {
    title: optTitle = "",
    icon: optIcon = null,
    heading: optHeading = null,
    highlight: optHighlight = null,
    highlightLabel: optHighlightLabel = null,
    transfer: optTransfer = null,
    isCancel: optIsCancel = true,
    isConfirm: optIsConfirm = true,
    confirmColor: optConfirmColor = "primary",
    timer: optTimer = 0,
    cancelText: optCancelText = "Close",
    confirmText: optConfirmText = "OK",
  } = opts;

  // Toast path
  if (optTimer > 0) {
    addToast(optTitle, optIcon, optTimer);
    return Promise.resolve(true);
  }

  // Dialog path
  title.value = optTitle;
  icon.value = optIcon;
  heading.value = optHeading;
  highlight.value = optHighlight;
  highlightLabel.value = optHighlightLabel;
  transfer.value = optTransfer;
  isOpen.value = true;
  isCancle.value = optIsCancel ?? true;
  isConfirm.value = optIsConfirm ?? true;
  confirmColor.value = optConfirmColor ?? "primary";
  cancelText.value = optCancelText ?? "Close";
  confirmText.value = optConfirmText ?? "OK";

  return new Promise((resolve) => {
    resolvePromise = resolve;
  });
};

const onConfirm = () => {
  isOpen.value = false;
  if (resolvePromise) resolvePromise(true);
};

const onCancel = () => {
  isOpen.value = false;
  if (resolvePromise) resolvePromise(false);
};

defineExpose({ openDialog });
</script>

<style scoped>
.toast-badge :deep(svg) {
  stroke-width: 3 !important;
}
/* ===== Confirmation dialog ===== */
.confirm-card {
  border-radius: 8px !important;
  overflow: hidden;
}
.confirm-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 28px 24px 22px;
}
.confirm-icon-wrap {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px !important;
  height: 56px !important;
  min-width: 56px;
  border-radius: 50%;
  border: 2px solid currentColor;
  margin-bottom: 14px;
}
.confirm-title {
  margin: 0 0 6px;
  font-size: 19px;
  font-weight: 500;
  line-height: 1.3;
}
.confirm-message {
  margin: 0 0 22px;
  font-size: 14px;
  line-height: 1.5;
  opacity: 0.7;
  /* a "\n" in the message starts a new line (e.g. debit / credit lines) */
  white-space: pre-line;
}
/* The message sits closer when the value panel follows it */
.confirm-message:has(+ .confirm-highlight),
.confirm-message:has(+ .confirm-transfer) {
  margin-bottom: 14px;
}

/* From → to timeline: a rail on the left from the red dot (money out) to
   the green dot (money in), the amount sitting on the rail between them */
.confirm-transfer {
  --rail-x: 12px; /* centre of the dots and the rail */
  position: relative;
  display: flex;
  flex-direction: column;
  margin-bottom: 22px;
  text-align: start;
}
/* the rail, drawn per step so it starts and ends exactly at the two dots
   whatever the height of the account names: red below the "from" dot,
   red → green through the amount, green into the "to" dot */
.confirm-transfer__step::after {
  position: absolute;
  inset-inline-start: calc(var(--rail-x) - 1px);
  inline-size: 2px;
  content: "";
}
.confirm-transfer__step--from::after {
  inset-block: 12px 0;
  background: rgb(var(--v-theme-error));
}
.confirm-transfer__step--move::after {
  inset-block: 0;
  background: linear-gradient(
    rgb(var(--v-theme-error)),
    rgb(var(--v-theme-success))
  );
}
.confirm-transfer__step--to::after {
  inset-block-start: 0;
  block-size: 12px;
  background: rgb(var(--v-theme-success));
}
/* dots and the arrow sit above the rail */
.confirm-transfer__dot,
.confirm-transfer__arrow {
  z-index: 1;
}
.confirm-transfer__step {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-inline-start: 36px;
}
.confirm-transfer__step--from {
  --box-color: var(--v-theme-error);
}
.confirm-transfer__step--to {
  --box-color: var(--v-theme-success);
}
/* plain dot on the rail, level with the label line */
.confirm-transfer__dot {
  position: absolute;
  inset-block-start: 6px;
  inset-inline-start: calc(var(--rail-x) - 6px);
  block-size: 12px;
  inline-size: 12px;
  border-radius: 50%;
  background: rgb(var(--box-color));
  /* surface-coloured ring so the rail ends cleanly at the dot */
  box-shadow: 0 0 0 3px rgb(var(--v-theme-surface));
}
.confirm-transfer__label {
  display: flex;
  align-items: center;
  min-block-size: 24px; /* lines up with the dot */
  color: rgb(var(--box-color));
  font-size: 12px;
  font-weight: 600;
}
.confirm-transfer__account {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.4;
}
.confirm-transfer__code {
  flex-shrink: 0;
  padding: 1px 8px;
  border-radius: 999px;
  background: rgba(var(--box-color), 0.14);
  color: rgb(var(--box-color));
  font-size: 12px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
/* the amount on the rail, between the two dots */
.confirm-transfer__step--move {
  justify-content: center;
  min-block-size: 56px;
  padding-block: 12px;
}
/* arrow marker sitting on the rail, level with the amount */
.confirm-transfer__arrow {
  position: absolute;
  inset-block-start: 50%;
  inset-inline-start: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  block-size: 24px;
  inline-size: 24px;
  border-radius: 50%;
  background: rgb(var(--v-theme-surface));
  box-shadow: inset 0 0 0 2px rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-primary));
  transform: translateY(-50%);
}
.confirm-transfer__amount {
  align-self: flex-start;
  padding: 2px 12px;
  border-radius: 999px;
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
  font-size: 20px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
/* Value panel: tinted box, small caption, big figure */
.confirm-highlight {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  width: 100%;
  margin: 0 0 20px;
  padding: 12px 14px 14px;
  border: 1px dashed rgba(var(--v-theme-primary), 0.45);
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.07);
}
.confirm-highlight__label {
  font-size: 12.5px;
  font-weight: 500;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}
.confirm-highlight__value {
  font-size: 36px;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: 0.5px;
  color: rgb(var(--v-theme-primary));
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.confirm-actions {
  display: flex;
  gap: 10px;
  width: 100%;
}
.confirm-btn {
  flex: 1;
  height: 38px !important;
  border-radius: 6px !important;
  font-size: 14px;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
}
.confirm-btn-cancel {
  background: rgba(127, 127, 127, 0.12) !important;
}

/* per-variant accent color for the icon ring + glyph */
.confirm-success .confirm-icon-wrap {
  color: #1d9e75;
}
.confirm-error .confirm-icon-wrap,
.confirm-delete .confirm-icon-wrap {
  color: #e24b4a;
}
.confirm-warning .confirm-icon-wrap {
  color: #ef9f27;
}
.confirm-info .confirm-icon-wrap {
  color: #378add;
}
.confirm-question .confirm-icon-wrap {
  color: rgb(var(--v-theme-primary));
}

/* ===== Toast stack ===== */
/* The wrapper centers the column at the top of the viewport. */
.toast-stack {
  position: fixed;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 3000;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  pointer-events: none;
  width: max-content;
  max-width: calc(100vw - 24px);
}

/* Each toast is a plain styled card — no Vuetify overlay involved. */
.toast-item {
  pointer-events: auto;
  min-width: 280px;
  max-width: 380px;
  border-radius: 8px;
  padding: 11px 14px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
}

/* ===== TransitionGroup animations ===== */
/* enter: fade + slide down into place */
.toast-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}
.toast-enter-to {
  opacity: 1;
  transform: translateY(0);
}
.toast-enter-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

/* leave: fade + slide up, with width/height collapsing handled by move */
.toast-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease,
    margin 0.25s ease,
    max-height 0.25s ease,
    padding 0.25s ease;
  overflow: hidden;
}
.toast-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 80px; /* roughly the toast's natural height */
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-12px);
  max-height: 0;
  margin-top: -10px; /* offset the stack gap as it collapses */
  padding-top: 0;
  padding-bottom: 0;
}
/* move: when a toast leaves, the remaining ones glide into their new spot */
.toast-move {
  transition: transform 0.25s ease;
}

.toast-row {
  display: flex;
  align-items: center;
  gap: 13px;
}
.toast-badge {
  flex-shrink: 0;
  width: 25px;
  height: 25px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.toast-body {
  flex: 1;
  min-width: 0;
}
.toast-title {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.3;
  color: #fff;
}
.toast-subtitle {
  margin: 1px 0 0;
  font-size: 12px;
  line-height: 1.3;
  color: rgba(255, 255, 255, 0.85);
}
.toast-close {
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.8) !important;
}

/* success */
.toast-success {
  background: #1d9e75;
}
.toast-success .toast-badge {
  background: #0f6e56;
}

/* error */
.toast-error {
  background: #e24b4a;
}
.toast-error .toast-badge {
  background: #a32d2d;
}

/* warning */
.toast-warning {
  background: #ef9f27;
}
.toast-warning .toast-badge {
  background: #ba7517;
}
.toast-warning .toast-subtitle {
  color: rgba(255, 255, 255, 0.92);
}

/* info */
.toast-info {
  background: #378add;
}
.toast-info .toast-badge {
  background: #185fa5;
}
</style>
