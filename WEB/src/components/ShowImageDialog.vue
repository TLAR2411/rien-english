<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  image: {
    type: String,
    default: null,
  },
  isDialogVisible: {
    type: Boolean,
    default: true,
  },
  loading: {
    type: Boolean,
    default: undefined,
  },
  maxZoom: {
    type: Number,
    default: 10,
  },
  minZoom: {
    type: Number,
    default: 0.1,
  },
  zoomStep: {
    type: Number,
    default: 0.25,
  },
  fitToScreenOnOpen: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits([
  "update:isDialogVisible",
  "onCloseDialog",
  "update:loading",
]);

// --- REFS ---
const containerRef = ref(null);
const imageRef = ref(null);

// --- STATE ---
const scale = ref(1);
const position = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const isPinching = ref(false);
const startPos = ref({ x: 0, y: 0 });
const startDist = ref(0);
const startScale = ref(1);
const imageLoaded = ref(false);
const imageNaturalSize = ref({ width: 0, height: 0 });
const containerSize = ref({ width: 0, height: 0 });

// --- COMPUTED ---
const imageStyle = computed(() => ({
  position: 'absolute',
  left: '50%',
  top: '50%',
  transform: `translate(-50%, -50%) translate(${position.value.x}px, ${position.value.y}px) scale(${scale.value})`,
  transition: isDragging.value || isPinching.value ? 'none' : 'transform 0.15s cubic-bezier(0.4, 0, 0.2, 1)',
  transformOrigin: 'center center',
  maxWidth: 'none',
  maxHeight: 'none',
  willChange: 'transform',
}));

const zoomPercentage = computed(() => Math.round(scale.value * 100));

const $loading = computed({
  get() { return props.loading !== undefined ? props.loading : false; },
  set(value) { emit("update:loading", value); },
});

// --- LIFECYCLE ---
onMounted(() => {
  disableBodyScroll();
  updateContainerSize();
  window.addEventListener('resize', updateContainerSize);
});

onUnmounted(() => {
  enableBodyScroll();
  window.removeEventListener('resize', updateContainerSize);
});

watch(() => props.isDialogVisible, (isVisible) => {
  if (isVisible) {
    disableBodyScroll();
    nextTick(() => {
      updateContainerSize();
      if (imageLoaded.value && props.fitToScreenOnOpen) {
        fitToContainer();
      }
    });
  } else {
    enableBodyScroll();
  }
});

watch(() => props.image, (newImage) => {
  if (newImage) {
    imageLoaded.value = false;
    resetView();
  }
});

// --- SCROLLBAR CONTROL ---
const disableBodyScroll = () => {
  document.body.style.overflow = 'hidden';
  document.documentElement.style.overflow = 'hidden';
};

const enableBodyScroll = () => {
  document.body.style.overflow = '';
  document.documentElement.style.overflow = '';
};

// --- UTILITIES ---
const updateContainerSize = () => {
  if (containerRef.value) {
    const rect = containerRef.value.getBoundingClientRect();
    containerSize.value = {
      width: rect.width,
      height: rect.height,
    };
    if (imageLoaded.value) {
      nextTick(() => fitToContainer());
    }
  }
};

const initImageSize = () => {
  if (imageRef.value) {
    if (imageRef.value.complete && imageRef.value.naturalWidth > 0) {
      imageNaturalSize.value = {
        width: imageRef.value.naturalWidth,
        height: imageRef.value.naturalHeight,
      };
      imageLoaded.value = true;
      if (props.fitToScreenOnOpen) {
        fitToContainer();
      }
    }
  }
};

// --- BOUNDARY CONSTRAINTS ---
const constrainPosition = () => {
  if (!imageLoaded.value) return;

  const scaledWidth = imageNaturalSize.value.width * scale.value;
  const scaledHeight = imageNaturalSize.value.height * scale.value;
  const containerW = containerSize.value.width;
  const containerH = containerSize.value.height;

  // Calculate boundaries
  const maxX = Math.max(0, (scaledWidth - containerW) / 2);
  const maxY = Math.max(0, (scaledHeight - containerH) / 2);

  let newX = position.value.x;
  let newY = position.value.y;

  // If image is smaller than container, center it
  if (scaledWidth <= containerW) {
    newX = 0;
  } else {
    // If image is larger than container, constrain movement
    newX = Math.min(maxX, Math.max(-maxX, newX));
  }

  if (scaledHeight <= containerH) {
    newY = 0;
  } else {
    newY = Math.min(maxY, Math.max(-maxY, newY));
  }

  position.value = { x: newX, y: newY };
};

// --- ZOOM FUNCTIONS ---
const zoomToPoint = (scaleFactor, clientX, clientY) => {
  if (!containerRef.value || !imageLoaded.value) return;

  const rect = containerRef.value.getBoundingClientRect();
  
  // Calculate point relative to image center
  const pointX = clientX - rect.left - rect.width / 2;
  const pointY = clientY - rect.top - rect.height / 2;

  const prevScale = scale.value;
  const newScale = Math.max(
    props.minZoom,
    Math.min(props.maxZoom, scale.value * scaleFactor)
  );

  if (newScale === prevScale) return;

  // Adjust position to zoom towards the point
  const scaleChange = newScale / prevScale;
  position.value.x = pointX - (pointX - position.value.x) * scaleChange;
  position.value.y = pointY - (pointY - position.value.y) * scaleChange;

  scale.value = newScale;
  nextTick(constrainPosition);
};

const zoomIn = () => {
  if (!containerRef.value) return;
  
  const rect = containerRef.value.getBoundingClientRect();
  const centerX = rect.left + rect.width / 2;
  const centerY = rect.top + rect.height / 2;
  
  zoomToPoint(1 + props.zoomStep, centerX, centerY);
};

const zoomOut = () => {
  if (!containerRef.value) return;
  
  const rect = containerRef.value.getBoundingClientRect();
  const centerX = rect.left + rect.width / 2;
  const centerY = rect.top + rect.height / 2;
  
  zoomToPoint(1 - props.zoomStep, centerX, centerY);
};

const resetView = () => {
  scale.value = 1;
  position.value = { x: 0, y: 0 };
};

const fitToContainer = () => {
  if (!imageLoaded.value || !containerRef.value) return;

  const containerW = containerSize.value.width;
  const containerH = containerSize.value.height;
  const imgW = imageNaturalSize.value.width;
  const imgH = imageNaturalSize.value.height;

  // Calculate scale to fit image within container
  const scaleX = containerW / imgW;
  const scaleY = containerH / imgH;
  const fitScale = Math.min(scaleX, scaleY);

  // Don't upscale small images
  scale.value = Math.min(1, fitScale);
  position.value = { x: 0, y: 0 };
};

const fitToWidth = () => {
  if (!imageLoaded.value || !containerRef.value) return;

  const containerW = containerSize.value.width;
  const imgW = imageNaturalSize.value.width;
  
  scale.value = containerW / imgW;
  position.value = { x: 0, y: 0 };
  nextTick(constrainPosition);
};

// --- EVENT HANDLERS ---
const onWheel = (e) => {
  e.preventDefault();
  
  if (e.ctrlKey || e.metaKey) {
    // Zoom
    const delta = e.deltaY < 0 ? 1.1 : 0.9;
    zoomToPoint(delta, e.clientX, e.clientY);
  } else {
    // Pan
    position.value.x -= e.deltaX * 0.5;
    position.value.y -= e.deltaY * 0.5;
    nextTick(constrainPosition);
  }
};

const startInteraction = (e) => {
  if (e.touches && e.touches.length === 2) {
    // Pinch start
    e.preventDefault();
    isPinching.value = true;
    isDragging.value = false;
    
    const touch1 = e.touches[0];
    const touch2 = e.touches[1];
    startDist.value = Math.hypot(
      touch1.clientX - touch2.clientX,
      touch1.clientY - touch2.clientY
    );
    startScale.value = scale.value;
    return;
  }

  // Drag start
  if (e.cancelable) e.preventDefault();
  
  isDragging.value = true;
  isPinching.value = false;
  
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  
  startPos.value = {
    x: clientX - position.value.x,
    y: clientY - position.value.y,
  };
};

const handleInteraction = (e) => {
  if (e.cancelable) e.preventDefault();

  // Handle pinch zoom
  if (isPinching.value && e.touches && e.touches.length === 2) {
    const touch1 = e.touches[0];
    const touch2 = e.touches[1];
    const newDist = Math.hypot(
      touch1.clientX - touch2.clientX,
      touch1.clientY - touch2.clientY
    );
    
    if (startDist.value === 0) return;
    
    const scaleMultiplier = newDist / startDist.value;
    const newScale = Math.max(
      props.minZoom,
      Math.min(props.maxZoom, startScale.value * scaleMultiplier)
    );
    
    scale.value = newScale;
    nextTick(constrainPosition);
    return;
  }

  // Handle drag/pan
  if (!isDragging.value) return;

  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  
  position.value = {
    x: clientX - startPos.value.x,
    y: clientY - startPos.value.y,
  };
};

const endInteraction = () => {
  isDragging.value = false;
  isPinching.value = false;
  nextTick(constrainPosition);
};

const onDoubleClick = (e) => {
  e.preventDefault();
  
  if (scale.value > 1) {
    resetView();
  } else {
    zoomToPoint(2, e.clientX, e.clientY);
  }
};

const onCloseDialog = () => {
  resetView();
  enableBodyScroll();
  emit("onCloseDialog");
  emit("update:isDialogVisible", false);
};
</script>

<template>
  <VDialog
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
    :model-value="isDialogVisible"
    @update:model-value="$emit('update:isDialogVisible', $event)"
    @after-enter="updateContainerSize"
    @after-leave="enableBodyScroll"
    content-class="no-scroll-dialog"
  >
    <VCard class="image-preview-dialog" tile>
      <VToolbar density="compact" color="surface" elevation="1" style="z-index: 10;">
        <VToolbarTitle class="text-subtitle-1">
          <!-- <span class="text-caption text-medium-emphasis">Image Preview</span> -->
        </VToolbarTitle>
        <VSpacer />
        
        <div class="d-flex align-center gap-2 mr-4">
          <VBtn
            icon="tabler-zoom-out"
            variant="text"
            size="small"
            @click="zoomOut"
            :disabled="scale <= minZoom"
            title="Zoom Out"
          />
          
          <VBtn
            icon="tabler-zoom-in"
            variant="text"
            size="small"
            @click="zoomIn"
            :disabled="scale >= maxZoom"
            title="Zoom In"
          />
          
          <VBtn
            icon="tabler-aspect-ratio"
            variant="text"
            size="small"
            @click="fitToContainer"
            title="Fit to Screen"
          />
          
          <VBtn
            icon="tabler-arrows-minimize"
            variant="text"
            size="small"
            @click="fitToWidth"
            title="Fit to Width"
          />
          
          <VBtn
            icon="tabler-refresh"
            variant="text"
            size="small"
            @click="resetView"
            title="Reset View (100%)"
          />
          
          <span class="zoom-percentage text-caption font-weight-bold mx-2">
            {{ zoomPercentage }}%
          </span>
        </div>
        
        <VDivider vertical class="mx-2" />
        
        <VBtn
          icon="tabler-x"
          variant="plain"
          @click="onCloseDialog"
          title="Close"
        />
      </VToolbar>

      <div 
        ref="containerRef"
        class="image-container"
        @wheel="onWheel"
        @mousedown="startInteraction"
        @mousemove="handleInteraction"
        @mouseup="endInteraction"
        @mouseleave="endInteraction"
        @touchstart="startInteraction"
        @touchmove="handleInteraction"
        @touchend="endInteraction"
        @touchcancel="endInteraction"
        @dblclick="onDoubleClick"
      >
        <img 
          ref="imageRef"
          :src="image"
          :style="imageStyle"
          draggable="false"
          alt="Image Preview"
          @load="initImageSize"
          @error="$loading = false"
          class="preview-image"
        />
        
        <!-- Loading overlay -->
        <div v-if="!imageLoaded" class="loading-overlay">
          <VProgressCircular indeterminate size="64" width="4" />
        </div>
        
        <!-- Zoom level indicator -->
        <div v-if="imageLoaded && scale !== 1" class="zoom-indicator">
          {{ zoomPercentage }}%
        </div>
      </div>
      
      <VOverlay
        v-model="$loading"
        contained
        persistent
        class="align-center justify-center"
        scrim="black"
      >
        <VProgressCircular indeterminate size="64" width="4" />
      </VOverlay>
    </VCard>
  </VDialog>
</template>

<style scoped>
.image-preview-dialog {
  background: black;
  height: 100vh;
  width: 100vw;
  display: flex;
  flex-direction: column;
  overflow: hidden !important;
}

.image-container {
  flex: 1;
  position: relative;
  background-color: rgb(243, 243, 243);
  /* background: 
    linear-gradient(45deg, #1a1a1a 25%, transparent 25%),
    linear-gradient(-45deg, #1a1a1a 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #1a1a1a 75%),
    linear-gradient(-45deg, transparent 75%, #1a1a1a 75%); */
  background-size: 20px 20px;
  background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
  cursor: grab;
  touch-action: none;
  user-select: none;
  overflow: hidden !important;
}

.image-container:active {
  cursor: grabbing;
}

.preview-image {
  pointer-events: none;
  user-select: none;
  -webkit-user-drag: none;
  -khtml-user-drag: none;
  -moz-user-drag: none;
  -o-user-drag: none;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.7);
  z-index: 1;
}

.zoom-indicator {
  position: absolute;
  bottom: 16px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
  z-index: 2;
  pointer-events: none;
}

.zoom-percentage {
  min-width: 45px;
  text-align: center;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

/* Force hide all scrollbars */
.image-container::-webkit-scrollbar,
.image-preview-dialog::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.image-container,
.image-preview-dialog {
  -ms-overflow-style: none;
  scrollbar-width: none;
  overflow: -moz-scrollbars-none;
}
</style>

<style>
/* Global styles to remove scrollbars when dialog is open */
.no-scroll-dialog {
  overflow: hidden !important;
}

.no-scroll-dialog .v-overlay__content {
  overflow: hidden !important;
}

.no-scroll-dialog .v-card {
  overflow: hidden !important;
}

/* Remove scrollbar from body when dialog is open */
body.v-dialog--active {
  overflow: hidden !important;
  position: fixed;
  width: 100%;
  height: 100%;
}

/* Hide all scrollbars in the dialog */
.v-dialog__container {
  overflow: hidden !important;
}

/* Ensure Vuetify dialog backdrop doesn't cause scrolling */
.v-overlay-scroll-blocked {
  overflow: hidden !important;
}
</style>