// Small helpers for quiz answers (shapes: components/learning/student/questions/QuizQuestion.vue)

/** The student gave an answer: an option id, some text, or at least one word. */
export const isAnswered = (answer) =>
  Array.isArray(answer) ? answer.length > 0 : answer != null && String(answer).trim() !== "";

/**
 * { [questionId]: answer } → the list the API wants: [{ question_id, answer }].
 * Only the quiz's current questions are sent (a saved draft may still hold a
 * question the teacher removed; the API rejects questions not in the quiz).
 */
export const toAnswerList = (answers, questions) =>
  questions.map(({ id }) => ({
    question_id: id,
    answer: isAnswered(answers[id]) ? answers[id] : null,
  }));
