// composables/useShareImage.js
//
// Turns a piece of the page into a PNG and hands it to the device share sheet
// (Telegram, WhatsApp, Messenger, …) or to a download.
import { ref } from "vue";
import html2canvas from "html2canvas";
import { useI18n } from "vue-i18n";
import { useDialog } from "./useDialog";

// Wait for fonts and images so nothing renders half-loaded.
const waitAssets = async (el) => {
  if (document.fonts?.ready) {
    try {
      await document.fonts.ready;
    } catch {}
  }
  const imgs = Array.from(el.querySelectorAll("img"));
  await Promise.all(
    imgs.map((img) =>
      img.complete
        ? Promise.resolve()
        : new Promise((res) => {
            img.addEventListener("load", res, { once: true });
            img.addEventListener("error", res, { once: true });
          }),
    ),
  );
};

// The tabler set draws every glyph with CSS mask-image, which html2canvas
// cannot paint — it fills the element's background-color instead, so each icon
// comes out as a solid box. Nest a real <img> built from the icon's own SVG,
// tinted with the colour the icon actually renders in.
const inlineIcons = (root) => {
  root.querySelectorAll('[class*="tabler-"]').forEach((el) => {
    const style = getComputedStyle(el);
    const raw = style.getPropertyValue("--svg").trim();
    if (!raw.startsWith("url(")) return;

    const uri = raw
      .slice(4, -1)
      .trim()
      .replace(/^["']|["']$/g, "");
    const comma = uri.indexOf(",");
    if (comma < 0) return;

    let svg;
    try {
      svg = decodeURIComponent(uri.slice(comma + 1));
    } catch {
      return; // Leave the icon alone rather than losing the whole capture.
    }
    svg = svg.replace(/(['"])black\1/g, `$1${style.color}$1`);

    const img = document.createElement("img");
    img.src = `data:image/svg+xml,${encodeURIComponent(svg)}`;
    img.style.cssText = "width:100%;height:100%;display:block;";

    // Stop the masked element painting its own box behind the image.
    el.style.backgroundColor = "transparent";
    el.style.backgroundImage = "none";
    el.appendChild(img);
  });
};

// Renders an element to a PNG blob.
//
// The element is cloned into an off-screen holder rather than captured where
// it sits: in place it may be inside a dialog's overlay transforms and scroll
// container (which html2canvas clips against) or inside a print-only wrapper
// that is display:none (which has no layout to capture at all).
export const renderElementToBlob = async (
  el,
  { width, padding = 0, background = "#fff" } = {},
) => {
  if (!el) return null;

  const appRoot = document.querySelector(".v-application") || document.body;
  const themeClass = Array.from(appRoot.classList).find((c) =>
    c.startsWith("v-theme--"),
  );

  const holder = document.createElement("div");
  if (themeClass) holder.classList.add(themeClass);
  // Absolute, not fixed: html2canvas double-counts scroll offset on fixed
  // elements, which shifts or crops the result.
  // `width` is the width of the finished image; padding is drawn inside it.
  holder.style.cssText = [
    "position:absolute",
    "top:0",
    "left:-10000px",
    "z-index:-1",
    "box-sizing:border-box",
    `width:${width || el.offsetWidth || 794}px`,
    `padding:${padding}px`,
    `background:${background}`,
  ].join(";");

  const clone = el.cloneNode(true);
  clone.style.display = "block";
  clone.style.overflow = "visible";
  holder.appendChild(clone);
  appRoot.appendChild(holder);

  try {
    inlineIcons(clone);
    await waitAssets(clone);

    const canvas = await html2canvas(holder, {
      scale: Math.min(window.devicePixelRatio || 1, 2),
      proxy: `${import.meta.env.VITE_API_URL}/h2c-proxy`,
      useCORS: false, // proxy handles it
      allowTaint: true, // safe because the image comes from our origin
      backgroundColor: background,
      logging: false,
      foreignObjectRendering: false,
      removeContainer: true,
    });

    return await new Promise((resolve) => canvas.toBlob(resolve, "image/png"));
  } finally {
    holder.remove();
  }
};

export const downloadBlob = (blob, name) => {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = name;
  a.click();
  URL.revokeObjectURL(url);
};

// Phones and tablets: their share sheet lists Telegram, Messenger, etc.
// On Windows / Mac the system share window only shows apps registered with
// the OS, and Telegram Desktop isn't one of them, so desktops copy instead.
export const isMobileDevice = () =>
  navigator.userAgentData?.mobile ??
  (/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent) ||
    (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1));

// Opens the Telegram app if installed (the browser may ask first). Nothing
// happens if it isn't installed; the image is already on the clipboard.
const openTelegramApp = () => {
  const a = document.createElement("a");
  a.href = "tg://";
  a.click();
};

const copyBlob = async (blob) => {
  try {
    if (!navigator.clipboard?.write || typeof ClipboardItem === "undefined")
      return false;
    await navigator.clipboard.write([new ClipboardItem({ "image/png": blob })]);
    return true;
  } catch {
    return false;
  }
};

export function useShareImage() {
  const { t } = useI18n();
  const { showDialog } = useDialog();

  const downloading = ref(false);
  const sharing = ref(false);

  const toast = (title, icon = "success", timer = 3000) =>
    showDialog({ title, icon, isConfirm: false, isCancel: false, timer });

  const downloadImage = async (el, name, options) => {
    if (downloading.value || sharing.value) return;
    downloading.value = true;
    try {
      const blob = await renderElementToBlob(el, options);
      if (blob) downloadBlob(blob, name);
    } finally {
      downloading.value = false;
    }
  };

  /**
   * Share a ready image file.
   *  - Phone / tablet: the system share sheet (Telegram is listed there)
   *  - Computer: copy it and open Telegram, so the user just presses Ctrl+V
   *  - Neither works: download it
   */
  const shareFile = async (file, { title, text } = {}) => {
    if (isMobileDevice() && navigator.canShare?.({ files: [file] })) {
      try {
        await navigator.share({ files: [file], title: title || file.name, text });
        return;
      } catch (error) {
        // The user dismissed the share sheet — nothing more to do.
        if (error?.name === "AbortError") return;
        // Anything else (no user gesture left, unsupported target) falls through.
      }
    }

    if (await copyBlob(file)) {
      if (!isMobileDevice()) openTelegramApp();
      const paste = /Mac/.test(navigator.userAgent) ? "⌘+V" : "Ctrl+V";
      toast(
        t("Image copied. Open the chat in Telegram and press {key} to send it.", { key: paste }),
        "success",
        5000,
      );
      return;
    }

    downloadBlob(file, file.name);
    toast(
      t("Sharing is not available here. The image was downloaded instead."),
      "info",
      4000,
    );
  };

  // Renders the element to a PNG, then shares it (see shareFile)
  const shareImage = async (el, name, options = {}) => {
    if (downloading.value || sharing.value) return;
    sharing.value = true;
    try {
      const blob = await renderElementToBlob(el, options);
      if (!blob) return;

      await shareFile(new File([blob], name, { type: "image/png" }), {
        title: options.title,
      });
    } finally {
      sharing.value = false;
    }
  };

  return { downloadImage, shareImage, shareFile, downloading, sharing };
}
