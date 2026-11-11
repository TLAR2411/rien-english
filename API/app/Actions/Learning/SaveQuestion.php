<?php

namespace App\Actions\Learning;

use App\Http\Requests\Learning\QuestionRequest;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use Illuminate\Support\Facades\DB;

/** Creates or updates a question and replaces its options, in one transaction. */
class SaveQuestion
{
    public function handle(QuestionRequest $request, Quiz $quiz, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($request, $quiz, $question) {
            $question ??= new Question(['quiz_id' => $quiz->id]);
            $question->fill($request->questionData())->save();

            $question->options()->delete();
            foreach ($request->optionsData() as $position => $option) {
                $question->options()->create([...$option, 'sort_order' => $position + 1]);
            }

            return $question->load('options');
        });
    }
}
