<script setup>
import { useI18n } from "vue-i18n";

defineOptions({ name: "UnderUserDiagramNode" });

const props = defineProps({
  node: { type: Object, required: true },
  collapsed: { type: Object, required: true },
  selectedId: { type: [Number, null], default: null },
  teamId: { type: [Number, null], default: null },
});

const emit = defineEmits(["toggle", "focus"]);

const { t, locale } = useI18n();

// Provided by UnderUserDiagram; null when the user cannot edit
const drag = inject("underUserDrag", null);

const cardClass = computed(() => ({
  "diagram-card--dragging": drag?.id === props.node.id,
  "diagram-card--drop": drag?.overId === props.node.id,
  "diagram-card--blocked":
    drag?.id && drag.id !== props.node.id && !drag.canDrop(props.node.id),
}));

const onDragStart = (e) => {
  e.dataTransfer.effectAllowed = "move";
  e.dataTransfer.setData("text/plain", String(props.node.id));
  drag.start(props.node);
};

const onDragOver = (e) => {
  if (!drag.canDrop(props.node.id)) return;
  e.preventDefault();
  e.dataTransfer.dropEffect = "move";
  drag.overId = props.node.id;
};

const onDragLeave = () => {
  if (drag.overId === props.node.id) drag.overId = null;
};

const hasChildren = computed(() => props.node.children.length > 0);
const isCollapsed = computed(() => props.collapsed.has(props.node.id));

// When every child has no team of their own (and sits on the same row),
// list them vertically instead of side by side — keeps wide teams narrow.
const isStack = computed(() => {
  const children = props.node.children;
  return (
    children.length > 1 &&
    children.every(
      (c) => !c.children.length && c.offset === children[0].offset,
    )
  );
});

const positionName = computed(() =>
  locale.value === "en"
    ? props.node.position_name_en || props.node.position_name_kh
    : props.node.position_name_kh || props.node.position_name_en,
);

const isMatch = computed(() => props.node.id === props.selectedId);
const isTeamMatch = computed(() => props.node.id === props.teamId);
</script>

<template>
  <li :style="{ '--offset': `${node.offset ?? 0}px` }">
    <div
      class="diagram-card"
      :class="[
        {
          'diagram-card--match': isMatch,
          'diagram-card--team': isTeamMatch && !isMatch,
        },
        cardClass,
      ]"
      :draggable="drag ? 'true' : undefined"
      @dragstart="drag && onDragStart($event)"
      @dragend="drag?.end()"
      @dragover="drag && onDragOver($event)"
      @dragleave="drag && onDragLeave()"
      @drop.prevent="drag?.drop(node)"
      @dblclick="emit('focus', node.id)"
    >
      <div class="d-flex align-center gap-3">
        <AppAvatar
          :image="node.image_path"
          :title="node.name_kh"
          color="primary"
          :is-show-full-image="false"
          :q="100"
        />
        <div class="text-start overflow-hidden">
          <div
            class="text-body-1 font-weight-medium text-high-emphasis text-truncate"
            style="font-family: inter, kantumruy, sans-serif !important"
          >
            {{ node.name_kh }}
          </div>
          <div
            class="text-caption text-medium-emphasis text-truncate"
            style="font-family: inter, kantumruy, sans-serif !important"
          >
            {{ node.name_en }}
          </div>
        </div>
      </div>

      <VDivider class="my-2" />

      <div class="d-flex align-center justify-space-between gap-2">
        <span
          class="text-caption text-truncate"
          :title="positionName"
          style="font-family: inter, kantumruy, sans-serif !important"
        >
          {{ positionName || "-" }}
        </span>
        <VChip size="x-small" color="primary" label>
          {{ t("Level") }} {{ node.position_level ?? "-" }}
        </VChip>
      </div>

      <VBtn
        v-if="hasChildren"
        class="diagram-toggle"
        size="x-small"
        rounded="pill"
        :color="isCollapsed ? 'primary' : 'secondary'"
        :variant="isCollapsed ? 'flat' : 'tonal'"
        @click="emit('toggle', node.id)"
      >
        <VIcon
          :icon="isCollapsed ? 'tabler-chevron-down' : 'tabler-chevron-up'"
          size="14"
          start
        />
        {{ node.children.length }}
      </VBtn>
    </div>

    <ul
      v-if="hasChildren && !isCollapsed"
      :class="{ 'leaf-stack': isStack }"
      :style="
        isStack
          ? { '--stack-offset': `${node.children[0].offset ?? 0}px` }
          : undefined
      "
    >
      <UnderUserDiagramNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :collapsed="collapsed"
        :selected-id="selectedId"
        :team-id="teamId"
        @toggle="emit('toggle', $event)"
        @focus="emit('focus', $event)"
      />
    </ul>
  </li>
</template>
