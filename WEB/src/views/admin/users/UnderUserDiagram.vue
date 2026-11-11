<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import UnderUserDiagramNode from "@/views/admin/users/UnderUserDiagramNode.vue";
import { useDialog } from "@/composables/useDialog";
import hasPermission from "@/utils/hasPermission";
import { useSettingStore } from "@/stores/settingStore";
import {
  useEventListener,
  useMediaQuery,
  useResizeObserver,
} from "@vueuse/core";
import UnderUserDiagramToolbar from "@/views/admin/users/UnderUserDiagramToolbar.vue";
import { useDisplay } from "vuetify";

const { t } = useI18n();
const { showDialog } = useDialog();

const settingStore = useSettingStore();
const { xs } = useDisplay();

const users = ref([]);
const underUsers = ref([]);
const isLoading = ref(false);
// 1. the user to move, 2. the supervisor (team) to move them under
const selectedUserId = ref(null);
const selectedTeamId = ref(null);
const zoom = ref(1);
const collapsed = ref(new Set());

// Build the hierarchy from the flat user list using under_user_id.
// Users whose supervisor is missing (inactive / other branch) become roots.
const tree = computed(() => {
  const nodes = new Map(users.value.map((u) => [u.id, { ...u, children: [] }]));

  const roots = [];
  nodes.forEach((node) => {
    const parent = nodes.get(node.under_user_id);
    if (parent && !isAncestor(nodes, node.id, parent)) {
      parent.children.push(node);
    } else {
      roots.push(node);
    }
  });

  return roots;
});

// Guard against circular under_user_id chains (A -> B -> A)
function isAncestor(nodes, id, parent) {
  const seen = new Set();
  let current = parent;
  while (current && !seen.has(current.id)) {
    if (current.id === id) return true;
    seen.add(current.id);
    current = nodes.get(current.under_user_id);
  }
  return false;
}

// Keep only branches that contain a selected user.
// A selected user keeps their whole team below them.
const filteredTree = computed(() => {
  const ids = [selectedUserId.value, selectedTeamId.value].filter(Boolean);
  if (!ids.length) return tree.value;

  const prune = (list) =>
    list.reduce((acc, node) => {
      if (ids.includes(node.id)) {
        acc.push(node);
      } else {
        const children = prune(node.children);
        if (children.length) acc.push({ ...node, children });
      }
      return acc;
    }, []);

  return prune(tree.value);
});

// Rows are grouped by level band (90-99, 80-89, ...). Must match card height + connector gaps in CSS.
const ROW_STEP = 160;
const bandOf = (level) => (level == null ? -1 : Math.floor(level / 10));

// Each band gets as many rows as it needs: a user in the same band as their
// supervisor takes a sub-row below them (depth), and the band grows to fit.
// Lower bands start after all rows of higher bands, so levels never mix.
const layout = computed(() => {
  const depth = new Map(); // user id -> sub-row inside their band
  const height = new Map(); // band -> number of sub-rows

  const measure = (list, parentBand, parentDepth) =>
    list.forEach((node) => {
      const band = bandOf(node.position_level);
      const d = band === parentBand ? parentDepth + 1 : 0;
      depth.set(node.id, d);
      height.set(band, Math.max(height.get(band) ?? 0, d + 1));
      measure(node.children, band, d);
    });
  measure(filteredTree.value, null, -1);

  // First row of each band, highest band on top
  const bandStart = new Map();
  let row = 0;
  [...height.keys()]
    .sort((a, b) => b - a)
    .forEach((band) => {
      bandStart.set(band, row);
      row += height.get(band);
    });

  // `offset` is the extra vertical space that pushes a card down to its row
  const place = (list, parentRow) =>
    list.map((node) => {
      const nodeRow = Math.max(
        parentRow + 1,
        bandStart.get(bandOf(node.position_level)) + depth.get(node.id),
      );
      return {
        ...node,
        offset: (nodeRow - parentRow - 1) * ROW_STEP,
        children: place(node.children, nodeRow),
      };
    });

  return {
    tree: place(filteredTree.value, -1),
    bandStarts: [...bandStart.entries()].map(([band, start]) => ({
      band,
      start,
    })),
  };
});

const layoutTree = computed(() => layout.value.tree);
const bands = computed(() => layout.value.bandStarts);

const onToggle = (id) => {
  const next = new Set(collapsed.value);
  next.has(id) ? next.delete(id) : next.add(id);
  collapsed.value = next;
};

const expandAll = () => {
  collapsed.value = new Set();
};

const collapseAll = () => {
  const ids = new Set();
  const walk = (list) =>
    list.forEach((node) => {
      if (node.children.length) ids.add(node.id);
      walk(node.children);
    });
  walk(tree.value);
  collapsed.value = ids;
};

// ── Drag & drop to change under_user_id ─────────────────────
const canEdit = hasPermission("edit-users");

const drag = reactive({
  id: null,
  overId: null,
  blocked: new Set(),
  canDrop: (id) => !!drag.id && !drag.blocked.has(id),
  start: (node) => startDrag(node),
  end: () => endDrag(),
  drop: (target) => dropOn(target),
});

provide("underUserDrag", canEdit ? drag : null);

// Invalid new supervisors: the user, all their subordinates and their current supervisor
function blockedFor(user) {
  const childrenOf = new Map();
  users.value.forEach((u) => {
    if (!childrenOf.has(u.under_user_id)) childrenOf.set(u.under_user_id, []);
    childrenOf.get(u.under_user_id).push(u.id);
  });

  const blocked = new Set([user.id]);
  const stack = [user.id];
  while (stack.length) {
    (childrenOf.get(stack.pop()) ?? []).forEach((id) => {
      if (!blocked.has(id)) {
        blocked.add(id);
        stack.push(id);
      }
    });
  }
  if (user.under_user_id) blocked.add(user.under_user_id);

  return blocked;
}

function startDrag(node) {
  drag.blocked = blockedFor(node);
  drag.id = node.id;
}

function endDrag() {
  drag.id = null;
  drag.overId = null;
  drag.blocked = new Set();
  stopAutoScroll();
}

// ── Auto-scroll while dragging near the edge of the diagram ──
// The browser does not scroll an overflow container during a native drag,
// so targets outside the visible area would be unreachable.
const EDGE = 70; // px from the edge where scrolling starts
const MAX_SPEED = 24; // px per frame at the very edge
const autoScroll = { vx: 0, vy: 0, raf: null };

// Speed grows as the pointer gets closer to the edge
const edgeSpeed = (pos, start, end) => {
  if (pos < start + EDGE) return -MAX_SPEED * Math.min(1, (start + EDGE - pos) / EDGE);
  if (pos > end - EDGE) return MAX_SPEED * Math.min(1, (pos - (end - EDGE)) / EDGE);
  return 0;
};

function autoScrollStep() {
  const el = diagramRef.value;
  if (!el || !drag.id || (!autoScroll.vx && !autoScroll.vy)) {
    autoScroll.raf = null;
    return;
  }
  el.scrollLeft += autoScroll.vx;
  el.scrollTop += autoScroll.vy;
  autoScroll.raf = requestAnimationFrame(autoScrollStep);
}

function stopAutoScroll() {
  autoScroll.vx = 0;
  autoScroll.vy = 0;
  if (autoScroll.raf) cancelAnimationFrame(autoScroll.raf);
  autoScroll.raf = null;
}

const onWrapperDragOver = (e) => {
  if (!drag.id) return;
  const rect = diagramRef.value.getBoundingClientRect();
  autoScroll.vx = edgeSpeed(e.clientX, rect.left, rect.right);
  autoScroll.vy = edgeSpeed(e.clientY, rect.top, rect.bottom);
  if ((autoScroll.vx || autoScroll.vy) && !autoScroll.raf) {
    autoScroll.raf = requestAnimationFrame(autoScrollStep);
  }
};

// Stop when the pointer leaves the diagram (not when moving between cards)
const onWrapperDragLeave = (e) => {
  if (!diagramRef.value?.contains(e.relatedTarget)) stopAutoScroll();
};

const findUser = (id) => users.value.find((u) => u.id === id);

function dropOn(target) {
  const user = findUser(drag.id);
  endDrag();
  if (user) moveUser(user, target);
}

// ── Select-based move ───────────────────────────────────────
const selectedUser = computed(() => findUser(selectedUserId.value));

// Supervisor options, excluding choices that would be invalid for the selected user
const teamOptions = computed(() => {
  if (!selectedUser.value) return underUsers.value;
  const blocked = blockedFor(selectedUser.value);
  return underUsers.value.filter((u) => !blocked.has(u.id));
});

watch(selectedUserId, () => {
  if (!teamOptions.value.some((u) => u.id === selectedTeamId.value)) {
    selectedTeamId.value = null;
  }
});

const onMove = async () => {
  const target =
    findUser(selectedTeamId.value) ??
    underUsers.value.find((u) => u.id === selectedTeamId.value);
  if (!selectedUser.value || !target) return;

  const moved = await moveUser(selectedUser.value, target);
  if (moved) selectedUserId.value = null;
};

// target === null means "remove supervisor". Returns true when saved.
async function moveUser(user, target) {
  if (target && target.id === user.under_user_id) return;
  if (!target && !user.under_user_id) return;

  const confirmed = await showDialog({
    title: target
      ? t("Move {user} under {team}?", {
          user: user.name_kh,
          team: target.name_kh,
        })
      : t("Remove supervisor of {user}?", { user: user.name_kh }),
    icon: "warning",
  });
  if (!confirmed) return;

  try {
    isLoading.value = true;
    const res = await api.patch("users-under-user", {
      id: user.id,
      under_user_id: target?.id ?? null,
    });
    if (res.data.status) {
      user.under_user_id = target?.id ?? null;
      if (target) {
        const next = new Set(collapsed.value);
        next.delete(target.id);
        collapsed.value = next;
      }
      return true;
    }
  } catch (error) {
    console.error("Failed to change under user:", error);
  } finally {
    isLoading.value = false;
  }
}

const onRootDragOver = (e) => {
  const user = findUser(drag.id);
  if (!user?.under_user_id) return;
  e.preventDefault();
  e.dataTransfer.dropEffect = "move";
  drag.overId = "root";
};

// ── Zoom & pan inside the diagram area ──────────────────────
const MIN_ZOOM = 0.3;
const MAX_ZOOM = 2;

// Change zoom while keeping the point under (clientX, clientY) in place.
// Without a point, zoom around the centre of the visible area.
const zoomTo = async (value, clientX, clientY) => {
  const el = diagramRef.value;
  const next = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, +value.toFixed(2)));
  if (!el || next === zoom.value) {
    zoom.value = next;
    return;
  }

  const rect = el.getBoundingClientRect();
  const mx = (clientX ?? rect.left + rect.width / 2) - rect.left;
  const my = (clientY ?? rect.top + rect.height / 2) - rect.top;
  const ratio = next / zoom.value;
  const left = (el.scrollLeft + mx) * ratio - mx;
  const top = (el.scrollTop + my) * ratio - my;

  zoom.value = next;
  await nextTick();
  el.scrollLeft = left;
  el.scrollTop = top;
};

const setZoom = (delta) => zoomTo(zoom.value + delta);

// Zoom so the whole tree fits in the visible area (never above 100%)
const fitToScreen = async () => {
  const el = diagramRef.value;
  if (!el) return;
  const style = getComputedStyle(el);
  const padX = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
  const padY = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);

  // scrollWidth/Height are the zoomed content size; divide to get 100% size
  const naturalW = (el.scrollWidth - padX) / zoom.value;
  const naturalH = (el.scrollHeight - padY) / zoom.value;
  const fit = Math.min(
    (el.clientWidth - padX) / naturalW,
    (el.clientHeight - padY) / naturalH,
    1,
  );

  await zoomTo(fit);
  el.scrollTo({ left: 0, top: 0 });
};

// Double-click a card: show only that person and their team
const onFocus = (id) => {
  selectedUserId.value = id;
  diagramRef.value?.scrollTo({ left: 0, top: 0 });
};

// Ctrl/⌘ + wheel zooms (trackpad pinch also sends ctrlKey); plain wheel scrolls
const onWheel = (e) => {
  if (!e.ctrlKey && !e.metaKey) return;
  e.preventDefault();
  // Clamp so one mouse-wheel notch is a ~20% step while pinch stays smooth
  const delta = Math.max(-50, Math.min(50, e.deltaY));
  zoomTo(zoom.value * Math.exp(-delta * 0.004), e.clientX, e.clientY);
};

// Drag on empty space to move around (mouse only; touch keeps native scrolling)
const pan = reactive({ active: false, x: 0, y: 0, left: 0, top: 0 });

const onPanStart = (e) => {
  if (e.pointerType !== "mouse" || e.button !== 0) return;
  if (e.target.closest(".diagram-card, button")) return;
  const el = diagramRef.value;
  Object.assign(pan, {
    active: true,
    x: e.clientX,
    y: e.clientY,
    left: el.scrollLeft,
    top: el.scrollTop,
  });
  el.setPointerCapture(e.pointerId);
};

const onPanMove = (e) => {
  if (!pan.active) return;
  const el = diagramRef.value;
  el.scrollLeft = pan.left - (e.clientX - pan.x);
  el.scrollTop = pan.top - (e.clientY - pan.y);
};

const onPanEnd = (e) => {
  if (!pan.active) return;
  pan.active = false;
  diagramRef.value?.releasePointerCapture(e.pointerId);
};

// Two-finger pinch to zoom on touch screens (one finger keeps native scrolling)
const isTouch = useMediaQuery("(pointer: coarse)");
let pinch = null;

const touchInfo = (e) => {
  const [a, b] = e.touches;
  return {
    dist: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY),
    x: (a.clientX + b.clientX) / 2,
    y: (a.clientY + b.clientY) / 2,
  };
};

const onTouchStart = (e) => {
  if (e.touches.length === 2) {
    pinch = { dist: touchInfo(e).dist, zoom: zoom.value };
  }
};

const onTouchMove = (e) => {
  if (!pinch || e.touches.length !== 2) return;
  e.preventDefault();
  const { dist, x, y } = touchInfo(e);
  zoomTo((pinch.zoom * dist) / pinch.dist, x, y);
};

const onTouchEnd = (e) => {
  if (e.touches.length < 2) pinch = null;
};

const fetchData = async () => {
  try {
    isLoading.value = true;
    const res = await api.get("users-diagram");
    if (res.data.status) {
      users.value = res.data.data;
    }
  } catch (error) {
    console.error("Failed to fetch data:", error);
  } finally {
    isLoading.value = false;
  }
};

const fetchUnderUsers = async () => {
  try {
    const res = await api.get("users-all-under-users", {
      params: { choose_branch_id: settingStore.branch_id || "*" },
    });
    if (res.data.status) {
      underUsers.value = res.data.data;
    }
  } catch (error) {
    console.error("Failed to fetch under users:", error);
  }
};

const refresh = () => Promise.all([fetchData(), fetchUnderUsers()]);

// ── Stretch the diagram area down to the bottom of the window ───
const BOTTOM_GAP = 24; // page padding + card border below the diagram
const pageRef = ref(null);
const diagramRef = ref(null);
const diagramHeight = ref(400);
const isFullscreen = ref(false);

const fitHeight = () => {
  const el = diagramRef.value;
  if (!el) return;
  const rect = el.getBoundingClientRect();
  // Full screen is position: fixed, so page scroll does not apply
  const top = rect.top + (isFullscreen.value ? 0 : window.scrollY);
  const gap = isFullscreen.value ? 8 : BOTTOM_GAP;
  const next = Math.max(240, window.innerHeight - top - gap);
  if (Math.abs(next - diagramHeight.value) > 1) diagramHeight.value = next;
};

useEventListener(window, "resize", fitHeight);
// Re-fit when the filter panel opens/closes or the header wraps
useResizeObserver(pageRef, fitHeight);

// ── Full screen (mainly for phones) ─────────────────────────
const toggleFullscreen = async () => {
  isFullscreen.value = !isFullscreen.value;
  document.body.style.overflow = isFullscreen.value ? "hidden" : "";
  await nextTick();
  fitHeight();
};

useEventListener(window, "keydown", (e) => {
  if (e.key === "Escape" && isFullscreen.value) toggleFullscreen();
});

onBeforeUnmount(() => {
  document.body.style.overflow = "";
});

const toolbarProps = computed(() => ({
  zoom: zoom.value,
  loading: isLoading.value,
  fullscreen: isFullscreen.value,
}));

const toolbarEvents = {
  expand: expandAll,
  collapse: collapseAll,
  "zoom-in": () => setZoom(0.1),
  "zoom-out": () => setZoom(-0.1),
  "zoom-reset": () => zoomTo(1),
  fit: fitToScreen,
  refresh,
  fullscreen: toggleFullscreen,
};

onMounted(async () => {
  // Start smaller on phones so more of the tree fits
  if (window.innerWidth < 600) zoom.value = 0.7;
  await nextTick();
  fitHeight();
  await refresh();
  await nextTick();
  fitHeight();
});

// Branch switched in the navbar: start fresh with the new branch's users
watch(
  () => settingStore.branch_id,
  async (n, o) => {
    if (n === o) return;
    endDrag();
    selectedUserId.value = null;
    selectedTeamId.value = null;
    collapsed.value = new Set();
    users.value = [];
    underUsers.value = [];
    diagramRef.value?.scrollTo({ left: 0, top: 0 });
    await refresh();
    await nextTick();
    fitHeight();
  },
);
</script>

<template>
  <div
    ref="pageRef"
    class="diagram-page"
    :class="{ 'is-fullscreen': isFullscreen }"
  >
    <AppCard
      title="Team Structure"
      title-icon="tabler-user-cog"
      isFullHeight
      is-filter
      show-filters
    >
      <template #filter>
        <VRow class="align-end" :dense="isTouch">
          <!-- 1. User | 2. New Team | Move — always one row -->
          <VCol cols="12" md="7" class="move-row">
            <AppAutocomplete
              v-model="selectedUserId"
              :items="users"
              :item-title="(item) => `${item.name_kh} (${item.name_en ?? ''})`"
              item-value="id"
              color="warning"
              clearable
              hide-details
              autocomplete="off"
            >
              <template #label>1. User</template>
              <template #item="{ props, item }">
                <VListItem
                  v-bind="props"
                  :title="`${item?.raw?.name_kh} (${item?.raw?.name_en ?? ''})`"
                  :subtitle="`${item?.raw?.position_name_kh ?? ''} · ${t('Level')} ${item?.raw?.position_level ?? '-'}`"
                >
                  <template #prepend>
                    <AppAvatar
                      :title="item?.raw?.name_kh"
                      :image="item?.raw?.image_path"
                      :size="40"
                      :is-show-full-image="false"
                    />
                    &nbsp;&nbsp;
                  </template>
                </VListItem>
              </template>
            </AppAutocomplete>
            <AppAutocomplete
              v-model="selectedTeamId"
              :items="teamOptions"
              :item-title="
                (item) =>
                  item.role_abbr
                    ? `${item.name_kh} (${item.role_abbr})`
                    : item.name_kh
              "
              item-value="id"
              color="success"
              clearable
              hide-details
              autocomplete="off"
            >
              <template #label>2. New Team</template>
              <template #item="{ props, item }">
                <VListItem
                  v-bind="props"
                  :title="`${item?.raw?.name_kh}  (${item?.raw?.role_abbr ?? ''})`"
                  :subtitle="`${item?.raw?.position_name ?? ''} · ${t('Level')} ${item?.raw?.position_level ?? '-'}`"
                >
                  <template #prepend>
                    <AppAvatar
                      :title="item?.raw?.name_kh"
                      :image="item?.raw?.image_path"
                      :size="40"
                      :is-show-full-image="false"
                    />
                    &nbsp;&nbsp;
                  </template>
                </VListItem>
              </template>
            </AppAutocomplete>
            <!-- Icon-only on phones so both selects keep enough width -->
            <VBtn
              v-if="canEdit"
              class="toolbar-height flex-shrink-0"
              :class="{ 'px-0 move-btn--icon': xs }"
              color="primary"
              :disabled="!selectedUserId || !selectedTeamId"
              :loading="isLoading"
              @click="onMove"
            >
              <VIcon icon="tabler-arrows-transfer-down" :start="!xs" />
              <template v-if="!xs">{{ t("Move") }}</template>
              <VTooltip v-if="xs" activator="parent">{{ t("Move") }}</VTooltip>
            </VBtn>
          </VCol>
          <!-- On phones the toolbar floats over the diagram instead -->
          <VCol
            v-if="!xs"
            cols="12"
            md
            class="d-flex justify-center justify-md-end"
          >
            <UnderUserDiagramToolbar
              v-bind="toolbarProps"
              v-on="toolbarEvents"
            />
          </VCol>
        </VRow>
        <!-- <VDivider  /> -->

        <div v-if="canEdit && !xs" class="drop-bar px-4 py-2">
          <div
            v-if="drag.id && findUser(drag.id)?.under_user_id"
            class="drop-root"
            :class="{ 'drop-root--over': drag.overId === 'root' }"
            @dragover="onRootDragOver"
            @dragleave="drag.overId = null"
            @drop.prevent="dropOn(null)"
          >
            <VIcon icon="tabler-user-off" size="18" class="me-2" />
            {{ t("Drop here to remove supervisor") }}
          </div>
          <div v-else-if="isTouch" class="text-caption text-medium-emphasis">
            <VIcon icon="tabler-hand-finger" size="16" class="me-1" />
            {{
              t(
                "Pinch to zoom, swipe to move. Use 1. User and 2. New Team to move a user",
              )
            }}
          </div>
          <div v-else class="text-caption text-medium-emphasis">
            <VIcon icon="tabler-drag-drop" size="16" class="me-1" />
            {{
              t("Drag a user onto another user to change who they are under")
            }}
            · {{ t("Ctrl + scroll to zoom, drag empty space to move") }}
            · {{ t("Double-click a user to show only their team") }}
          </div>
        </div>
      </template>

      <div class="diagram-area">
        <div
          ref="diagramRef"
          class="diagram-wrapper"
          :class="{ 'is-dragging': drag.id, 'is-panning': pan.active }"
          :style="{ blockSize: `${diagramHeight}px` }"
          @wheel="onWheel"
          @pointerdown="onPanStart"
          @pointermove="onPanMove"
          @pointerup="onPanEnd"
          @pointercancel="onPanEnd"
          @dragover="onWrapperDragOver"
          @dragleave="onWrapperDragLeave"
          @touchstart="onTouchStart"
          @touchmove="onTouchMove"
          @touchend="onTouchEnd"
          @touchcancel="onTouchEnd"
        >
          <div
            v-if="isLoading && !users.length"
            class="d-flex justify-center pa-10"
          >
            <VProgressCircular indeterminate color="primary" />
          </div>

          <div
            v-else-if="!filteredTree.length"
            class="text-center text-medium-emphasis pa-10"
          >
            {{ t("No data available") }}
          </div>

          <div v-else class="diagram-stage">
            <!-- Outside the zoomed area so the lines always span the full width -->
            <div
              v-for="row in bands"
              :key="row.band"
              class="level-row"
              :style="{
                insetBlockStart: `${(row.start * ROW_STEP - 16) * zoom}px`,
              }"
            />

            <div class="diagram-zoom" :style="{ zoom }">
              <ul class="org-tree">
                <UnderUserDiagramNode
                  v-for="node in layoutTree"
                  :key="node.id"
                  :node="node"
                  :collapsed="collapsed"
                  :selected-id="selectedUserId"
                  :team-id="selectedTeamId"
                  @toggle="onToggle"
                  @focus="onFocus"
                />
              </ul>
            </div>
          </div>
        </div>

        <!-- Phones: toolbar floats over the diagram, outside the scroll area -->
        <div v-if="xs" class="diagram-float">
          <UnderUserDiagramToolbar
            v-bind="toolbarProps"
            floating
            v-on="toolbarEvents"
          />
        </div>
      </div>
    </AppCard>
  </div>
</template>

<style lang="scss" scoped>
// Match the height of the outlined select inputs
.toolbar-height {
  block-size: 38px !important;
}

// Full screen: cover the whole viewport (menus/dialogs have higher z-index)
.diagram-page.is-fullscreen {
  position: fixed;
  z-index: 1100;
  background: rgb(var(--v-theme-background));
  inset: 0;
  overflow: hidden;

  // AppCard caps its height at 100dvh - 84px; lift that in full screen
  --appcard-max: 100dvh;

  :deep(.data-list-height) {
    max-block-size: 100dvh;
  }
}

.diagram-area {
  position: relative;
}

.diagram-float {
  position: absolute;
  z-index: 2;
  display: flex;
  justify-content: center;
  inset-block-end: 12px;
  inset-inline: 8px;
  pointer-events: none;

  > * {
    pointer-events: auto;
  }
}

// User | Team | Move on one line; the two selects share the width equally
.move-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;

  :deep(.app-select) {
    flex: 1 1 0;
    min-inline-size: 0;
  }
}

.move-btn--icon {
  min-inline-size: 42px !important;
}

.diagram-wrapper {
  overflow: auto;
  padding-block: 24px;
  padding-inline: 16px;
  cursor: grab;
  overscroll-behavior: contain;
}

.diagram-wrapper.is-panning {
  cursor: grabbing;
  user-select: none;
}

// Stage grows with the zoomed tree (CSS zoom affects layout, unlike transform)
.diagram-stage {
  position: relative;
  inline-size: max-content;
  min-inline-size: 100%;
}

$line: rgba(var(--v-border-color), 0.5);

.org-tree,
.org-tree :deep(ul) {
  position: relative;
  display: flex;
  justify-content: center;
  padding: 0;
  margin: 0;
  list-style: none;
}

.org-tree :deep(ul) {
  padding-block-start: 24px;
}

.diagram-zoom {
  position: relative;
  z-index: 1;
}

// Level band strips behind each row
.level-row {
  position: absolute;
  z-index: 0;
  block-size: 144px; // card (112) + 16 above + 16 below
  border-block-start: 1px dashed rgba(var(--v-border-color), 0.4);
  inset-inline: 0;
}

.org-tree {
  z-index: 1;
}

// --offset pushes a card down to its level row; connectors stretch with it
.org-tree :deep(li) {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-block: calc(24px + var(--offset, 0px)) 0;
  padding-inline: 8px;
}

// Horizontal connectors between siblings
.org-tree :deep(li)::before,
.org-tree :deep(li)::after {
  position: absolute;
  border-block-start: 2px solid $line;
  block-size: calc(24px + var(--offset, 0px));
  content: "";
  inline-size: 50%;
  inset-block-start: 0;
}

.org-tree :deep(li)::before {
  inset-inline-end: 50%;
}

.org-tree :deep(li)::after {
  border-inline-start: 2px solid $line;
  inset-inline-start: 50%;
}

.org-tree :deep(li:first-child)::before,
.org-tree :deep(li:last-child)::after {
  border: 0 none;
}

.org-tree :deep(li:last-child)::before {
  border-inline-end: 2px solid $line;
  border-start-end-radius: 6px;
}

.org-tree :deep(li:first-child)::after {
  border-start-start-radius: 6px;
}

// Only child: straight vertical line, no horizontal bar
.org-tree :deep(li:only-child)::before {
  display: none;
}

.org-tree :deep(li:only-child)::after {
  border: 0 none;
  border-inline-start: 2px solid $line;
  border-radius: 0;
}

// Vertical connector from parent down to children row
.org-tree :deep(ul ul)::before {
  position: absolute;
  border-inline-start: 2px solid $line;
  block-size: 24px;
  content: "";
  inline-size: 0;
  inset-block-start: 0;
  inset-inline-start: 50%;
}

// Root-level: no connectors above top nodes
.org-tree > :deep(li) {
  padding-block-start: var(--offset, 0px);
}

.org-tree > :deep(li)::before,
.org-tree > :deep(li)::after {
  display: none;
}

// ── Stacked team: staff listed vertically under their supervisor ──
// Geometry (card 230px wide, 112px tall): the stack is centred under the
// parent card, the rail sits 20px inside the parent card's left edge and
// each card hangs off the rail with a 16px tick.
$stack-pad: 72px; // rail (56px) + tick (16px)
$stack-rail: 56px;
$card-mid: 56px; // half the card height

.org-tree :deep(ul.leaf-stack) {
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
  padding-block-start: calc(24px + var(--stack-offset, 0px));
  padding-inline-start: $stack-pad;
}

// Rail from the parent card down to the middle of the last card
.org-tree :deep(ul.leaf-stack)::before {
  block-size: auto;
  inset-block: 0 $card-mid;
  inset-inline-start: $stack-rail;
}

.org-tree :deep(ul.leaf-stack > li) {
  padding: 0;
}

.org-tree :deep(ul.leaf-stack > li + li) {
  margin-block-start: 16px;
}

// Tick from the rail to each card
.org-tree :deep(ul.leaf-stack > li)::before {
  display: block;
  border: 0 none;
  border-block-start: 2px solid $line;
  border-radius: 0;
  block-size: 0;
  inline-size: $stack-pad - $stack-rail;
  inset-block-start: $card-mid;
  inset-inline: auto 100%;
}

.org-tree :deep(ul.leaf-stack > li)::after {
  display: none;
}

.org-tree :deep(.diagram-card) {
  position: relative;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 2px 6px rgba(var(--v-shadow-key-umbra-color), 0.08);
  block-size: 112px;
  inline-size: 230px;
  padding-block: 12px 18px;
  padding-inline: 12px;
  transition:
    box-shadow 0.15s ease,
    border-color 0.15s ease;

  &:hover {
    box-shadow: 0 4px 14px rgba(var(--v-shadow-key-umbra-color), 0.16);
  }
}

.org-tree :deep(.diagram-card--match) {
  border-color: rgb(var(--v-theme-warning));
  box-shadow: 0 0 0 2px rgba(var(--v-theme-warning), 0.35);
}

.org-tree :deep(.diagram-card--team) {
  border-color: rgb(var(--v-theme-success));
  box-shadow: 0 0 0 2px rgba(var(--v-theme-success), 0.35);
}

.drop-bar {
  min-block-size: 56px;
  display: flex;
  align-items: center;
}

.drop-root {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  border: 2px dashed rgba(var(--v-theme-error), 0.5);
  border-radius: 8px;
  color: rgb(var(--v-theme-error));
  padding-block: 8px;
  transition: background 0.15s ease;
}

.drop-root--over {
  background: rgba(var(--v-theme-error), 0.1);
}

.org-tree :deep(.diagram-card) {
  cursor: default;
}

.org-tree :deep(.diagram-card[draggable="true"]) {
  cursor: move;
}

// Children must not fire dragleave while hovering a card
.is-dragging :deep(.diagram-card *) {
  pointer-events: none;
}

.org-tree :deep(.diagram-card--dragging) {
  opacity: 0.4;
}

.org-tree :deep(.diagram-card--blocked) {
  opacity: 0.5;
}

.org-tree :deep(.diagram-card--drop) {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 3px rgba(var(--v-theme-primary), 0.35);
  transform: scale(1.03);
}

.org-tree :deep(.diagram-toggle) {
  position: absolute;
  inset-block-end: -11px;
  inset-inline-start: 50%;
  transform: translateX(-50%);
}

// Phones: tighter spacing so the diagram gets more room
@media (max-width: 599px) {
  .drop-bar {
    min-block-size: 40px;
  }

  .diagram-wrapper {
    padding-block: 16px 72px; // room for the floating toolbar
    padding-inline: 8px;
  }
}
</style>
