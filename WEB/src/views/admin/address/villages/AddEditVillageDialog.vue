<script setup>
// Village create / edit: the shared address form at the "village" level
import AddressFormDialog from "@/views/admin/address/AddressFormDialog.vue";

defineProps({
  itemData: { type: Object, default: () => ({}) },
  isDialogVisible: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
});

defineEmits(["onCreate", "onUpdate", "update:isDialogVisible"]);
</script>

<template>
  <AddressFormDialog
    level="village"
    :item-data="itemData"
    :is-dialog-visible="isDialogVisible"
    :loading="loading"
    @on-create="(data, cb) => $emit('onCreate', data, cb)"
    @on-update="(data, cb) => $emit('onUpdate', data, cb)"
    @update:is-dialog-visible="(v) => $emit('update:isDialogVisible', v)"
  />
</template>
