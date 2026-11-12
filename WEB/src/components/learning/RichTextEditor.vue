<script setup>
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { StarterKit } from "@tiptap/starter-kit";
import { Underline } from "@tiptap/extension-underline";
import { Link } from "@tiptap/extension-link";
import { Image } from "@tiptap/extension-image";
import { Placeholder } from "@tiptap/extension-placeholder";
import { Table } from "@tiptap/extension-table";
import { TableRow } from "@tiptap/extension-table-row";
import { TableCell } from "@tiptap/extension-table-cell";
import { TableHeader } from "@tiptap/extension-table-header";

/**
 * Text editor for lessons (TipTap). v-model is HTML.
 * The tags it can make must match the server's allowed list:
 * API/config/purify.php → configs.lesson.
 *
 * `uploadImage` (optional): async (file) => url. When set, an image button is shown.
 */
const props = defineProps({
  modelValue: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  uploadImage: { type: Function, default: null },
});

const emit = defineEmits(["update:modelValue"]);

const editor = useEditor({
  content: props.modelValue || "",
  extensions: [
    StarterKit.configure({ heading: { levels: [2, 3] }, codeBlock: false }),
    Underline,
    Link.configure({ openOnClick: false, HTMLAttributes: { target: "_blank", rel: "noopener" } }),
    Image,
    Placeholder.configure({ placeholder: props.placeholder }),
    Table.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  onUpdate: ({ editor }) => emit("update:modelValue", editor.isEmpty ? "" : editor.getHTML()),
});

// Outside change (e.g. "Insert template", or the lesson finished loading)
watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value || value === editor.value.getHTML()) return;
    editor.value.commands.setContent(value || "", false);
  },
);

onBeforeUnmount(() => editor.value?.destroy());

const chain = () => editor.value.chain().focus();

const setLink = () => {
  const previous = editor.value.getAttributes("link").href || "";
  const url = window.prompt("Link (https://…)", previous);
  if (url === null) return;
  if (url === "") return chain().unsetLink().run();
  chain().extendMarkRange("link").setLink({ href: url }).run();
};

const fileInput = ref(null);
const isUploading = ref(false);
const onImagePicked = async (event) => {
  const file = event.target.files?.[0];
  event.target.value = "";
  if (!file) return;
  isUploading.value = true;
  try {
    const url = await props.uploadImage(file);
    if (url) chain().setImage({ src: url, alt: file.name }).run();
  } finally {
    isUploading.value = false;
  }
};

// Toolbar: [icon, title, run, isActive?]. Groups are split by a divider.
const groups = computed(() => {
  if (!editor.value) return [];
  const e = editor.value;
  return [
    [
      ["tabler-h-2", "Heading", () => chain().toggleHeading({ level: 2 }).run(), e.isActive("heading", { level: 2 })],
      ["tabler-h-3", "Small heading", () => chain().toggleHeading({ level: 3 }).run(), e.isActive("heading", { level: 3 })],
    ],
    [
      ["tabler-bold", "Bold", () => chain().toggleBold().run(), e.isActive("bold")],
      ["tabler-italic", "Italic", () => chain().toggleItalic().run(), e.isActive("italic")],
      ["tabler-underline", "Underline", () => chain().toggleUnderline().run(), e.isActive("underline")],
      ["tabler-strikethrough", "Strike", () => chain().toggleStrike().run(), e.isActive("strike")],
    ],
    [
      ["tabler-list", "Bullet list", () => chain().toggleBulletList().run(), e.isActive("bulletList")],
      ["tabler-list-numbers", "Numbered list", () => chain().toggleOrderedList().run(), e.isActive("orderedList")],
      ["tabler-quote", "Quote", () => chain().toggleBlockquote().run(), e.isActive("blockquote")],
      ["tabler-link", "Link", setLink, e.isActive("link")],
    ],
    [
      ["tabler-table-plus", "Insert table", () => chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()],
      ...(e.isActive("table")
        ? [
            ["tabler-row-insert-bottom", "Add row", () => chain().addRowAfter().run()],
            ["tabler-column-insert-right", "Add column", () => chain().addColumnAfter().run()],
            ["tabler-row-remove", "Delete row", () => chain().deleteRow().run()],
            ["tabler-column-remove", "Delete column", () => chain().deleteColumn().run()],
            ["tabler-table-minus", "Delete table", () => chain().deleteTable().run()],
          ]
        : []),
    ],
    [
      ["tabler-arrow-back-up", "Undo", () => chain().undo().run()],
      ["tabler-arrow-forward-up", "Redo", () => chain().redo().run()],
    ],
  ];
});
</script>

<template>
  <div class="rich-editor">
    <div v-if="editor" class="rich-editor__toolbar">
      <template v-for="(group, index) in groups" :key="index">
        <VDivider v-if="index" vertical class="mx-1" />
        <IconBtn
          v-for="[icon, title, run, active] in group"
          :key="icon"
          size="small"
          rounded
          :variant="active ? 'tonal' : 'text'"
          :color="active ? 'primary' : 'default'"
          :title="$t(title)"
          :aria-label="$t(title)"
          @click="run"
        >
          <VIcon :icon="icon" size="20" />
        </IconBtn>
      </template>

      <template v-if="uploadImage">
        <VDivider vertical class="mx-1" />
        <IconBtn
          size="small"
          rounded
          :loading="isUploading"
          :title="$t('Insert image')"
          :aria-label="$t('Insert image')"
          @click="fileInput?.click()"
        >
          <VIcon icon="tabler-photo" size="20" />
        </IconBtn>
        <input ref="fileInput" type="file" accept="image/*" hidden @change="onImagePicked" />
      </template>
    </div>

    <EditorContent :editor="editor" class="rich-editor__content lesson-content" />
  </div>
</template>

<style lang="scss" scoped>
.rich-editor {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  overflow: hidden;
}

.rich-editor__toolbar {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 2px;
  padding: 6px 8px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
}

.rich-editor__content :deep(.ProseMirror) {
  min-height: 320px;
  padding: 16px 20px;
  outline: none;
}

.rich-editor__content :deep(p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
  color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
}

.rich-editor__content :deep(.selectedCell) {
  background: rgba(var(--v-theme-primary), 0.08);
}
</style>
