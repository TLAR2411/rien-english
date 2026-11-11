// Small helpers for quiz answers (shapes: components/learning/student/questions/QuizQuestion.vue)

/** The student gave an answer: an option id, some text, or at least one word. */
export const isAnswered = (answer) =>
  Array.isArray(answer) ? answer.length > 0 : answer != null && String(answer).trim() !== "";

/** { [questionId]: answer } → the list the API wants: [{ question_id, answer }] */
export const toAnswerList = (answers) =>
  Object.entries(answers).map(([questionId, answer]) => ({
    question_id: Number(questionId),
    answer: isAnswered(answer) ? answer : null,
  }));
