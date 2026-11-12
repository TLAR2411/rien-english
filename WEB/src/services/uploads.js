import { api } from "@/utils/api";

/**
 * Upload one file to the learning uploads (type: "audio" | "image").
 * Returns the file's URL, or null when the API refused it (the error is shown by utils/api.js).
 */
export async function uploadFile(file, type) {
  const body = new FormData();
  body.append("type", type);
  body.append("file", file);

  try {
    const res = await api.post("admin/uploads", body);
    return res.data.data.url;
  } catch {
    return null;
  }
}

export const uploadImage = (file) => uploadFile(file, "image");
