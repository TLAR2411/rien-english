/**
 * Keeps a student's unsent quiz answers in this browser tab (sessionStorage),
 * so a page refresh does not lose them. Cleared after submit.
 * Storage can be blocked (private mode…): then it simply does not save.
 */
export function useQuizDraft(quizId) {
  const key = () => `rien-quiz-draft-${toValue(quizId)}`;

  const read = () => {
    try {
      const draft = JSON.parse(sessionStorage.getItem(key()) ?? "null");
      return draft && typeof draft.answers === "object" ? draft : null;
    } catch {
      return null;
    }
  };

  const write = (answers, startedAt) => {
    try {
      sessionStorage.setItem(key(), JSON.stringify({ answers, started_at: startedAt }));
    } catch {
      // not saved: fine
    }
  };

  const clear = () => {
    try {
      sessionStorage.removeItem(key());
    } catch {
      // nothing to clear
    }
  };

  return { read, write, clear };
}
