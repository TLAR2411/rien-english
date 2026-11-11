import html2canvas from "html2canvas";
import { jsPDF } from "jspdf";

/**
 * Render a DOM element to an A4 PDF.
 *
 * The browser draws the element (so Khmer text shapes correctly), html2canvas
 * turns it into an image, and the image is sliced across as many A4 pages as
 * needed. The element must be rendered (it can be off-screen, not display:none).
 *
 * @param {HTMLElement} element
 * @param {{ orientation?: "landscape" | "portrait", margin?: number }} options
 * @returns {Promise<jsPDF>}
 */
/** Draw a DOM element to a canvas (white background, 2× for sharp text). */
export function elementToCanvas(element) {
  return html2canvas(element, {
    scale: 2,
    backgroundColor: "#ffffff",
    useCORS: true,
  });
}

/**
 * Render a DOM element to a PNG image file, e.g. for sharing to chat apps.
 *
 * @param {HTMLElement} element
 * @param {string} fileName
 * @returns {Promise<File>}
 */
export async function elementToImageFile(element, fileName = "image.png") {
  const canvas = await elementToCanvas(element);
  const blob = await new Promise((resolve) =>
    canvas.toBlob(resolve, "image/png"),
  );
  return new File([blob], fileName, { type: "image/png" });
}

export async function elementToPdf(element, options = {}) {
  const { orientation = "landscape", margin = 8 } = options;

  const canvas = await elementToCanvas(element);

  const pdf = new jsPDF({ orientation, unit: "mm", format: "a4" });
  const pageWidth = pdf.internal.pageSize.getWidth();
  const pageHeight = pdf.internal.pageSize.getHeight();

  const imageWidth = pageWidth - margin * 2;
  const pxPerMm = canvas.width / imageWidth;
  const pageHeightPx = Math.floor((pageHeight - margin * 2) * pxPerMm);

  // Slice the tall image into page-sized pieces
  for (let y = 0, page = 0; y < canvas.height; y += pageHeightPx, page++) {
    const sliceHeight = Math.min(pageHeightPx, canvas.height - y);
    const slice = document.createElement("canvas");
    slice.width = canvas.width;
    slice.height = sliceHeight;
    slice
      .getContext("2d")
      .drawImage(
        canvas,
        0,
        y,
        canvas.width,
        sliceHeight,
        0,
        0,
        canvas.width,
        sliceHeight,
      );

    if (page > 0) pdf.addPage();
    pdf.addImage(
      slice.toDataURL("image/jpeg", 0.92),
      "JPEG",
      margin,
      margin,
      imageWidth,
      sliceHeight / pxPerMm,
    );
  }

  return pdf;
}
