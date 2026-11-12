// YouTube links in lessons. The API checks the link too (App\Support\YouTube).

const HOSTS = ["youtube.com", "www.youtube.com", "m.youtube.com", "youtu.be", "youtube-nocookie.com", "www.youtube-nocookie.com"];
const ID = /^[\w-]{11}$/;

/** The video ID (11 characters) from a YouTube link, or null. */
export const youtubeId = (link) => {
  let url;
  try {
    url = new URL(String(link ?? "").trim());
  } catch {
    return null;
  }
  if (!["http:", "https:"].includes(url.protocol) || !HOSTS.includes(url.hostname)) return null;

  const [first, second] = url.pathname.split("/").filter(Boolean);
  let id = null;
  if (url.hostname === "youtu.be") id = first;
  else if (first === "watch") id = url.searchParams.get("v");
  else if (["embed", "shorts", "live", "v"].includes(first)) id = second;

  return id && ID.test(id) ? id : null;
};

/** YouTube's player for one video ("nocookie" = no tracking until the student presses play). */
export const youtubeEmbedUrl = (id) => `https://www.youtube-nocookie.com/embed/${id}?rel=0`;

/** Small picture of the video, for the lesson editor. */
export const youtubeThumbnail = (id) => `https://i.ytimg.com/vi/${id}/mqdefault.jpg`;

/** Form rule: empty, or a link to one YouTube video. */
export const youtubeValidator = (value) => !value || !!youtubeId(value) || "Please paste a link to a YouTube video";
