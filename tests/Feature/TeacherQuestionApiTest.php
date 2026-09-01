<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class TeacherQuestionApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_teacher_can_list_blooms(): void
    {
        $this->withToken($this->teacherToken())
            ->getJson('/api/v1/teacher/blooms')
            ->assertOk()
            ->assertJsonPath('blooms.0.id', 'B11')
            ->assertJsonPath('blooms.0.title', '記憶（事實/定義）')
            ->assertJsonPath('blooms.2.id', 'B13')
            ->assertJsonPath('blooms.2.title', '記憶（事實/定義）')
            ->assertJsonPath('blooms.6.id', 'B31')
            ->assertJsonPath('blooms.6.title', '應用（程式實作/填空）')
            ->assertJsonPath('blooms.10.id', 'B42')
            ->assertJsonPath('blooms.10.title', '分析（程式除錯/判讀）')
            ->assertJsonPath('blooms.17.id', 'B63')
            ->assertJsonCount(18, 'blooms');
    }

    public function test_teacher_can_create_question_with_lowercase_bloom_code(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => 'php註解',
                'type' => Question::TYPE_CHOICE,
                'question_content' => 'PHP網頁的多行註解是用哪一個符號？',
                'bloom_id' => 'b11',
                'knowledge_card_ids' => $cardIds,
                'options' => [
                    ['title' => '/* */', 'is_answer' => true],
                    ['title' => '//', 'is_answer' => false],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('question.bloom_id', 'B11');
    }

    public function test_teacher_can_list_course_knowledge_cards(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(2);

        $this->withToken($this->teacherToken())
            ->getJson("/api/v1/teacher/courses/{$courseId}/knowledge-cards")
            ->assertOk()
            ->assertJsonCount(2, 'knowledge_cards')
            ->assertJsonPath('knowledge_cards.0.id', $cardIds[0]);
    }

    public function test_course_knowledge_cards_use_concept_names_grouped_by_chapter(): void
    {
        $token = $this->teacherToken();
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '知識點課',
            'description' => '知識卡正規化',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $topicId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ])->json('topic.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$topicId}/chapters", [
            'name' => '第一章 PHP 簡介',
        ])->json('chapter.id');

        $explainId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '說明',
        ])->json('unit.id');

        $practiceId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '實作變數01',
        ])->json('unit.id');

        $explainCardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$explainId}/knowledge-cards", [
            'title' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
            'content' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
            'example' => '$name = "小明";',
        ])->json('knowledge_card.id');

        $this->withToken($token)->postJson("/api/v1/teacher/units/{$practiceId}/knowledge-cards", [
            'title' => '實作變數01',
            'content' => '$age = 21;',
            'example' => '$age = 21;',
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/knowledge-cards")
            ->assertOk()
            ->assertJsonCount(1, 'knowledge_cards')
            ->assertJsonPath('knowledge_cards.0.id', $explainCardId)
            ->assertJsonPath('knowledge_cards.0.title', '變數')
            ->assertJsonPath('knowledge_cards.0.chapter_name', '第一章 PHP 簡介')
            ->assertJsonPath('knowledge_cards.0.topic_name', 'PHP 基礎');
    }

    public function test_course_knowledge_cards_unique_per_topic_and_omit_lecture_rows(): void
    {
        $token = $this->teacherToken();
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '知識點課2',
            'description' => '主題內去重',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $topicId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 實作',
        ])->json('topic.id');

        foreach (['第一章', '第二章'] as $chapterName) {
            $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$topicId}/chapters", [
                'name' => $chapterName,
            ])->json('chapter.id');

            $explainId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
                'name' => '說明',
            ])->json('unit.id');

            $practiceId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
                'name' => '實作變數01',
            ])->json('unit.id');

            $this->withToken($token)->postJson("/api/v1/teacher/units/{$explainId}/knowledge-cards", [
                'title' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
                'content' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
            ]);

            $this->withToken($token)->postJson("/api/v1/teacher/units/{$practiceId}/knowledge-cards", [
                'title' => '實作變數01',
                'content' => '$name = "PHP";',
                'example' => '$name = "PHP";',
            ]);
        }

        $lectureTopicId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => '只有說明',
        ])->json('topic.id');
        $lectureChapterId = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$lectureTopicId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');
        $lectureUnitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$lectureChapterId}/units", [
            'name' => '說明',
        ])->json('unit.id');
        $this->withToken($token)->postJson("/api/v1/teacher/units/{$lectureUnitId}/knowledge-cards", [
            'title' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
            'content' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/knowledge-cards")
            ->assertOk()
            ->assertJsonCount(1, 'knowledge_cards')
            ->assertJsonPath('knowledge_cards.0.title', '變數')
            ->assertJsonPath('knowledge_cards.0.topic_name', 'PHP 實作');
    }

    public function test_teacher_can_create_choice_question_with_auto_solo(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(2);

        $response = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => 'php註解',
                'type' => Question::TYPE_CHOICE,
                'question_content' => 'PHP網頁的多行註解是用哪一個符號？',
                'bloom_id' => 'B1',
                'description' => '多行註解',
                'knowledge_card_ids' => $cardIds,
                'options' => [
                    ['title' => '<?php ?>', 'is_answer' => false],
                    ['title' => '/* */', 'is_answer' => true],
                    ['title' => '<!-- -->', 'is_answer' => false],
                    ['title' => '//', 'is_answer' => false],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('question.type', Question::TYPE_CHOICE)
            ->assertJsonPath('question.teacher_id', Course::query()->findOrFail($courseId)->teacher_id)
            ->assertJsonPath('question.bloom_id', 'B1')
            ->assertJsonPath('question.description', '多行註解')
            ->assertJsonPath('question.knowledge_card_ids', $cardIds)
            ->assertJsonPath('question.knowledge_cards.0.example', '$name = "PHP";')
            ->assertJsonPath('question.options.1.is_answer', true)
            ->assertJsonPath('question.options.1.solo', QuestionOption::SOLO_CORRECT)
            ->assertJsonPath('question.options.0.solo', QuestionOption::SOLO_WRONG)
            ->assertJsonCount(4, 'question.options');

        $this->assertDatabaseHas('question_knowledge_cards', [
            'question_id' => $response->json('question.id'),
            'knowledge_card_id' => $cardIds[0],
        ]);
    }

    public function test_teacher_can_create_true_false_question(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '連結字串',
                'type' => Question::TYPE_TRUE_FALSE,
                'question_content' => 'PHP用「.」來連接字串',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => $cardIds,
                'options' => [
                    ['title' => '是', 'is_answer' => true],
                    ['title' => '非', 'is_answer' => false],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('question.type', Question::TYPE_TRUE_FALSE)
            ->assertJsonPath('question.options.0.solo', 2)
            ->assertJsonPath('question.options.1.solo', 1)
            ->assertJsonCount(2, 'question.options');
    }

    public function test_teacher_can_create_fill_question_by_sub_id(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => 'PI常數',
                'type' => Question::TYPE_FILL,
                'question_content' => '（1）("（2）", 3.14);',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => $cardIds,
                'sub_answers' => [
                    ['sub_id' => 1, 'answer' => 'define'],
                    ['sub_id' => 2, 'answer' => 'PI'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('question.type', Question::TYPE_FILL)
            ->assertJsonPath('question.sub_answers.0.sub_id', 1)
            ->assertJsonPath('question.sub_answers.0.answer', 'define')
            ->assertJsonPath('question.sub_answers.1.sub_id', 2)
            ->assertJsonPath('question.sub_answers.1.answer', 'PI')
            ->assertJsonPath('question.sub_answers.0.solo', QuestionSubAnswer::SOLO_CORRECT)
            ->assertJsonCount(0, 'question.options');
    }

    public function test_teacher_can_create_debug_and_interpret_questions(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '找出 PHP 錯誤',
                'type' => Question::TYPE_DEBUG,
                'question_content' => "請找出以下 PHP 程式的錯誤並修正。\n<?php\n\$name = \"Tom\"\necho \$name;",
                'bloom_id' => 'B4',
                'knowledge_card_ids' => $cardIds,
                'sub_answers' => [
                    [
                        'sub_id' => 2,
                        'answer' => '$name = "Tom";',
                        'description' => 'PHP 敘述結尾缺少分號 ;',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('question.sub_answers.0.sub_id', 2)
            ->assertJsonPath('question.sub_answers.0.answer', '$name = "Tom";')
            ->assertJsonPath('question.sub_answers.0.description', 'PHP 敘述結尾缺少分號 ;');

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '解讀這段程式',
                'type' => Question::TYPE_INTERPRET,
                'question_content' => "請解讀以下 PHP 程式，說明最後會輸出什麼。\n<!--code-stem-->\n\$a = 5;\n\$b = 10;\nif (\$a < \$b) {\n    echo \"A\";\n}",
                'bloom_id' => 'B2',
                'knowledge_card_ids' => $cardIds,
                'sub_answers' => [
                    [
                        'sub_id' => 1,
                        'answer' => 'A',
                        'description' => '$a 的值為 5，$b 的值為 10，因為 5 < 10 成立，所以執行 echo "A"。',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('question.type', Question::TYPE_INTERPRET)
            ->assertJsonPath('question.sub_answers.0.answer', 'A')
            ->assertJsonPath('question.sub_answers.0.description', '$a 的值為 5，$b 的值為 10，因為 5 < 10 成立，所以執行 echo "A"。');
    }

    public function test_teacher_can_create_coding_question_without_ai_fields(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '輸出 hello',
                'type' => Question::TYPE_CODING,
                'question_content' => '請輸出 hello',
                'bloom_id' => 'B3',
                'knowledge_card_ids' => $cardIds,
            ])
            ->assertCreated()
            ->assertJsonPath('question.type', Question::TYPE_CODING)
            ->assertJsonPath('question.show_example', false)
            ->assertJsonPath('question.starter_code', null)
            ->assertJsonPath('question.expected_output', null)
            ->assertJsonPath('question.reference_answer', null)
            ->assertJsonCount(0, 'question.options')
            ->assertJsonCount(0, 'question.sub_answers');

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '兩數相加',
                'type' => Question::TYPE_CODING,
                'question_content' => '請使用 PHP 撰寫程式，將兩個數字相加後輸出結果。',
                'bloom_id' => 'B3',
                'knowledge_card_ids' => $cardIds,
                'starter_code' => "\$a = 10;\n\$b = 20;",
                'expected_output' => '30',
                'reference_answer' => "\$a = 10;\n\$b = 20;\n\$result = \$a + \$b;\necho \$result;",
            ])
            ->assertCreated()
            ->assertJsonPath('question.starter_code', "\$a = 10;\n\$b = 20;")
            ->assertJsonPath('question.expected_output', '30')
            ->assertJsonPath('question.reference_answer', "\$a = 10;\n\$b = 20;\n\$result = \$a + \$b;\necho \$result;");
    }

    public function test_teacher_can_show_knowledge_card_example_to_students(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '輸出 hello',
                'type' => Question::TYPE_CODING,
                'question_content' => '請輸出 hello',
                'bloom_id' => 'B3',
                'show_example' => true,
                'knowledge_card_ids' => $cardIds,
            ])
            ->assertCreated()
            ->assertJsonPath('question.show_example', true)
            ->assertJsonPath('question.knowledge_cards.0.example', '$name = "PHP";');
    }

    public function test_teacher_can_list_show_update_and_delete_question(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(2);
        $questionId = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", $this->choicePayload($cardIds))
            ->json('question.id');

        $this->withToken($this->teacherToken())
            ->getJson("/api/v1/teacher/courses/{$courseId}/questions")
            ->assertOk()
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $questionId);

        $this->withToken($this->teacherToken())
            ->getJson("/api/v1/teacher/questions/{$questionId}")
            ->assertOk()
            ->assertJsonPath('question.title', 'php註解');

        $this->withToken($this->teacherToken())
            ->putJson("/api/v1/teacher/questions/{$questionId}", [
                'title' => 'php註解（改）',
                'type' => Question::TYPE_CHOICE,
                'question_content' => '改過的題幹',
                'bloom_id' => 'B2',
                'knowledge_card_ids' => [$cardIds[0]],
                'options' => [
                    ['title' => 'A', 'is_answer' => false],
                    ['title' => 'B', 'is_answer' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('question.title', 'php註解（改）')
            ->assertJsonPath('question.bloom_id', 'B2')
            ->assertJsonPath('question.knowledge_card_ids', [$cardIds[0]])
            ->assertJsonCount(2, 'question.options');

        $this->withToken($this->teacherToken())
            ->deleteJson("/api/v1/teacher/questions/{$questionId}")
            ->assertOk()
            ->assertJsonPath('message', '題目已刪除');

        $this->assertDatabaseMissing('questions', ['id' => $questionId]);
    }

    public function test_teacher_cannot_create_on_other_teachers_course(): void
    {
        $courseId = Course::query()
            ->where('name', '網際系統設計')
            ->where('class_name', '資應')
            ->value('id');

        $cardId = KnowledgeCard::query()->create([
            'unit_id' => null,
            'title' => '佔位卡',
            'content' => 'x',
            'sort_order' => 1,
        ])->id;

        $this->withToken($this->loginToken('teacher@school.edu.tw'))
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '不該成功',
                'type' => Question::TYPE_CODING,
                'question_content' => 'x',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => [$cardId],
            ])
            ->assertNotFound();
    }

    public function test_teacher_cannot_use_other_course_knowledge_card(): void
    {
        [$courseId] = $this->seedCourseWithCards(1);
        $otherCardId = KnowledgeCard::query()->create([
            'unit_id' => null,
            'title' => '別課的卡',
            'content' => 'x',
            'sort_order' => 1,
        ])->id;

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '掛錯卡',
                'type' => Question::TYPE_CODING,
                'question_content' => 'x',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => [$otherCardId],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('knowledge_card_ids');
    }

    public function test_choice_requires_exactly_one_correct_option(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '沒正解',
                'type' => Question::TYPE_CHOICE,
                'question_content' => 'x',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => $cardIds,
                'options' => [
                    ['title' => 'A', 'is_answer' => false],
                    ['title' => 'B', 'is_answer' => false],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('options');
    }

    public function test_fill_requires_sub_answers(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '沒格子',
                'type' => Question::TYPE_FILL,
                'question_content' => '（1）',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => $cardIds,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sub_answers');
    }

    public function test_student_cannot_create_teacher_question(): void
    {
        $courseId = Course::query()->where('class_name', '資應')->value('id');

        $this->withToken($this->loginToken('1411131000'))
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '學生不能出題',
                'type' => Question::TYPE_CODING,
                'question_content' => 'x',
                'bloom_id' => 'B1',
                'knowledge_card_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_teacher_cannot_delete_question_with_records(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);
        $questionId = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", $this->choicePayload($cardIds))
            ->json('question.id');

        QuestionRecord::query()->create([
            'student_id' => 1,
            'question_id' => $questionId,
            'result' => '1',
            'system_status' => QuestionRecord::STATUS_CORRECT,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
        ]);

        $this->withToken($this->teacherToken())
            ->deleteJson("/api/v1/teacher/questions/{$questionId}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');
    }

    public function test_teacher_can_review_coding_record_manually(): void
    {
        [$courseId, $cardIds] = $this->seedCourseWithCards(1);
        $questionId = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'title' => '輸出 hello',
                'type' => Question::TYPE_CODING,
                'question_content' => '請輸出 hello',
                'bloom_id' => 'B3',
                'knowledge_card_ids' => $cardIds,
            ])
            ->json('question.id');

        $record = QuestionRecord::query()->create([
            'student_id' => 1,
            'question_id' => $questionId,
            'result' => '<?php echo "hello";',
            'system_status' => QuestionRecord::STATUS_PENDING,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
        ]);

        $this->withToken($this->teacherToken())
            ->getJson("/api/v1/teacher/courses/{$courseId}/question-records")
            ->assertOk()
            ->assertJsonPath('records.0.result', '<?php echo "hello";')
            ->assertJsonPath('records.0.question_bloom_id', 'B3')
            ->assertJsonPath('records.0.bloom_id', null)
            ->assertJsonPath('records.0.expected_output', null)
            ->assertJsonPath('records.0.reference_answer', null);

        $this->withToken($this->teacherToken())
            ->putJson("/api/v1/teacher/question-records/{$record->id}", [
                'bloom_id' => 'B4',
            ])
            ->assertOk()
            ->assertJsonPath('record.bloom_id', 'B4')
            ->assertJsonPath('record.solo', QuestionRecord::SOLO_CORRECT)
            ->assertJsonPath('record.system_status', QuestionRecord::STATUS_CORRECT);

        $this->withToken($this->teacherToken())
            ->putJson("/api/v1/teacher/question-records/{$record->id}", [
                'bloom_id' => 'b21',
            ])
            ->assertOk()
            ->assertJsonPath('record.bloom_id', 'B21')
            ->assertJsonPath('record.solo', QuestionRecord::SOLO_WRONG)
            ->assertJsonPath('record.system_status', QuestionRecord::STATUS_WRONG);
    }

    /**
     * @return array{0: int, 1: list<int>}
     */
    private function seedCourseWithCards(int $cardCount): array
    {
        $token = $this->teacherToken();
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '出題測試課',
            'description' => '教師出題',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $topicId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ])->json('topic.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$topicId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');

        $unitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '註解',
        ])->json('unit.id');

        $cardIds = [];
        for ($i = 1; $i <= $cardCount; $i++) {
            $cardIds[] = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
                'title' => "知識卡 {$i}",
                'content' => "內容 {$i}",
                'example' => '$name = "PHP";',
            ])->json('knowledge_card.id');
        }

        return [$courseId, $cardIds];
    }

    /**
     * @param  list<int>  $cardIds
     * @return array<string, mixed>
     */
    private function choicePayload(array $cardIds): array
    {
        return [
            'title' => 'php註解',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '多行註解？',
            'bloom_id' => 'B1',
            'knowledge_card_ids' => $cardIds,
            'options' => [
                ['title' => 'A', 'is_answer' => false],
                ['title' => 'B', 'is_answer' => true],
            ],
        ];
    }

    private function teacherToken(): string
    {
        return $this->loginToken('teacher@school.edu.tw');
    }

    private function loginToken(string $account): string
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/v1/auth/login', [
            'account' => $account,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk();

        return $response->json('token');
    }
}
