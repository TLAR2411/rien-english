/**
 * Plays an example sentence: its uploaded audio when it has one, otherwise the
 * browser reads it aloud (speechSynthesis, American English).
 * Only one sentence plays at a time.
 *
 *   const { canPlay, isPlaying, toggle } = useSpeech()
 *   toggle({ id, sentence, audio_url })
 */
const hasSpeech = typeof window !== "undefined" && "speechSynthesis" in window;

export function useSpeech() {
  const playingId = ref(null);
  let player = null;

  const stop = () => {
    player?.pause();
    player = null;
    if (hasSpeech) window.speechSynthesis.cancel();
    playingId.value = null;
  };

  const speak = (text) => {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "en-US";
    utterance.rate = 0.9; // a little slower for beginners
    utterance.onend = () => (playingId.value = null);
    utterance.onerror = () => (playingId.value = null);
    window.speechSynthesis.speak(utterance);
  };

  const toggle = (item) => {
    const wasPlaying = playingId.value === item.id;
    stop();
    if (wasPlaying) return;

    playingId.value = item.id;
    if (item.audio_url) {
      player = new Audio(item.audio_url);
      player.onended = () => (playingId.value = null);
      player.play().catch(() => (playingId.value = null));
    } else {
      speak(item.sentence);
    }
  };

  onBeforeUnmount(stop);

  return {
    canPlay: (item) => Boolean(item?.audio_url || (hasSpeech && item?.sentence)),
    isPlaying: (item) => playingId.value === item?.id,
    toggle,
    stop,
  };
}
