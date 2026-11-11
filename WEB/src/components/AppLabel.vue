<script setup>
import { Title } from "chart.js";

const props = defineProps({
  title: { tpye: String, default: "Lable" },
  icon: { tpye: String, default: "tabler-code" },
  isBorderTop: { type: Boolean, default: true },
  isBorderButton: { type: Boolean, default: true },
});
</script>

<template>
  <VCol cols="12" class="pb-0">
    <div class="d-flex flex-row align-center text-primary">
      <!-- <VIcon start :icon="icon" /> -->
       
      <h4 class="pt-1 flex-grow-0 text-no-wrap text-primary">
        {{ $t(title) }}
      </h4>
      <VDivider class="ml-1 w-100" />
    </div>
  </VCol>
</template>
