<script setup>
import { computed } from "vue";
import formatNoneZero from "@/utils/formater/formatNoneZero";

const props = defineProps({
  titleKh: { type: String, default: "" },
  titleEn: { type: String, default: "" },
  totalLabel: { type: String, default: "" },
  total: { type: [Number, String], default: 0 },
  currency: { type: String, default: null },
  rows: { type: Array, default: () => [] },
  /** Vuetify theme colour name: primary | success | warning | error | info */
  accent: { type: String, default: "primary" },
  /** Hide the footer total row (when totals live inside `rows` as headers). */
  hideTotal: { type: Boolean, default: false },
});

// Accept both the balance-sheet shape (account_*) and the P&L shape
// (chart_account_*) without the caller having to remap.
const normalizedRows = computed(() =>
  (props.rows || []).map((r, i) => {
    const hasAmount = r.total_amount != null || r.amount != null;
    return {
      key: r.account_code ?? r.chart_account_code ?? r.key ?? i,
      isHeader: !!r.header,
      icon: r.icon ?? null,
      code: r.account_code ?? r.chart_account_code ?? "",
      nameKh: r.account_name_kh ?? r.chart_account_name_kh ?? "",
      nameEn: r.account_name_en ?? r.chart_account_name_en ?? "",
      // A row without an amount key (e.g. a plain grouping header) shows nothing.
      amount: hasAmount ? (r.total_amount ?? r.amount) : null,
    };
  }),
);

// When the section uses inline header rows, indent the detail rows beneath them.
const hasHeaderRows = computed(() =>
  normalizedRows.value.some((r) => r.isHeader),
);
</script>

<template>
  <section class="bss" :style="{ '--bss-accent': `var(--v-theme-${accent})` }">
    <header class="bss__head">
      <span class="bss__kh">{{ titleKh }}</span>
      <span class="bss__en">{{ titleEn }}</span>
    </header>

    <div class="bss__rows">
      <template v-if="normalizedRows.length">
        <div
          v-for="row in normalizedRows"
          :key="row.key"
          class="bss__row"
          :class="{
            'bss__row--header': row.isHeader,
            'bss__row--indent': hasHeaderRows && !row.isHeader,
          }"
        >
          <div class="bss__label">
            <VIcon
              v-if="row.icon"
              class="bss__icon"
              :icon="row.icon"
              size="18"
            />
            <span v-if="row.code" class="bss__code">{{ row.code }}</span>
            <span class="bss__names">
              <span class="bss__name-kh">{{ row.nameKh }}</span>
              <span v-if="row.nameEn" class="bss__name-en">{{
                row.nameEn
              }}</span>
            </span>
          </div>
          <div class="bss__amount">
            {{ row.amount === null ? "" : formatNoneZero(row.amount) }}
          </div>
        </div>
      </template>
      <div v-else class="bss__row bss__row--empty">—</div>
    </div>

    <footer v-if="!hideTotal" class="bss__total">
      <span class="bss__total-label">{{ totalLabel }}</span>
      <span class="bss__total-amount">{{
        formatNoneZero(total, currency)
      }}</span>
    </footer>
  </section>
</template>

<style scoped>
.bss {
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
}

.bss__head {
  position: relative;
  display: flex;
  align-items: baseline;
  gap: 8px;
  padding: 10px 14px;
  background: rgb(var(--bss-accent));
  border-bottom: 1px solid rgb(var(--bss-accent));
}
.bss__kh {
  font-size: 0.95rem;
  font-weight: 700;
  color: #fff;
}
.bss__en {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.8);
}

.bss__rows {
  flex: 1 1 auto;
}
.bss__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 9px 14px;
}
.bss__row:nth-child(even) {
  background: rgba(var(--v-theme-on-surface), 0.025);
}
.bss__row:hover {
  background: rgb(var(--v-theme-primary), 0.06);
}
.bss__row--empty {
  justify-content: center;
  color: rgba(var(--v-theme-on-surface), 0.45);
}
.bss__row--header {
  background: rgb(var(--bss-accent), 0.09) !important;
  border-block: 1px solid rgb(var(--bss-accent), 0.18);
}
/* .bss__row--header:not(:first-child) {
  margin-top: 6px;
} */
.bss__row--header .bss__label {
  flex-direction: row;
  align-items: center;
  gap: 8px;
}
.bss__row--header .bss__name-kh {
  font-size: 0.9rem;
  font-weight: 700;
}
.bss__icon {
  flex-shrink: 0;
  color: rgb(var(--bss-accent));
}
.bss__row--header .bss__amount {
  font-weight: 700;
}
.bss__row--indent {
  padding-inline-start: 32px;
}
.bss__row--indent .bss__name-kh {
  color: rgba(var(--v-theme-on-surface), 0.8);
}

.bss__label {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.3;
}
.bss__names {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.bss__code {
  font-size: 0.7rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.45);
}
.bss__name-kh {
  font-size: 0.875rem;
}
.bss__name-en {
  font-size: 0.72rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

/* On wider screens, put the account code on the same line as the name. */
@media (min-width: 960px) {
  .bss__label {
    flex-direction: row;
    align-items: baseline;
    gap: 10px;
  }
  .bss__code {
    flex-shrink: 0;
    min-width: 3.5rem;
  }
}

.bss__amount {
  flex-shrink: 0;
  min-width: 7.5rem;
  text-align: right;
  font-size: 0.9rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.bss__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 11px 14px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--bss-accent), 0.06);
  font-weight: 700;
}
.bss__total-label {
  font-size: 0.85rem;
}
.bss__total-amount {
  flex-shrink: 0;
  min-width: 7.5rem;
  text-align: right;
  font-size: 1rem;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
</style>
