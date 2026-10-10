<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionSubAnswer;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = Teacher::first();
        if (!$teacher) {
            $this->command?->error('請先執行 DatabaseSeeder 建立教師。');
            return;
        }

        $courses = Course::all();
        if ($courses->isEmpty()) {
            $this->command?->error('請先執行 DatabaseSeeder 建立課程。');
            return;
        }

        $student = Student::first();

        // 確保學生註冊在所有測試課程中
        if ($student) {
            foreach ($courses as $c) {
                if (!$student->courses()->where('courses.id', $c->id)->exists()) {
                    $student->courses()->attach($c->id);
                }
            }
        }

        // 對每個課程都建立完整題庫 (包含實作題)
        foreach ($courses as $course) {
            // 如果該課程已經有題目則跳過
            if ($course->questions()->exists()) {
                continue;
            }

            // 1. 選擇題
            $q1 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP 變數符號',
                'type' => Question::TYPE_CHOICE,
                'question_content' => 'PHP 宣告變數時，名稱前面要加什麼符號？',
                'bloom_id' => 'B11',
                'description' => '基礎語法',
                'show_example' => false,
            ]);
            QuestionOption::create([
                'question_id' => $q1->id,
                'title' => '$',
                'description' => '變數名稱前面要加 $',
                'is_answer' => true,
                'solo' => 2,
            ]);
            QuestionOption::create([
                'question_id' => $q1->id,
                'title' => '@',
                'description' => '這不是正解',
                'is_answer' => false,
                'solo' => 1,
            ]);

            // 2. 除錯題
            $q2 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => '找出少了錢字號',
                'type' => Question::TYPE_DEBUG,
                'question_content' => "下列程式哪一行少了 \$？\n<!--debug-code-->\n1. echo \"hi\";\n2. \$age = 21;\n3. name = \"Ada\";",
                'bloom_id' => 'B41',
                'description' => '除錯',
                'show_example' => false,
            ]);
            QuestionSubAnswer::create([
                'question_id' => $q2->id,
                'sub_id' => 3,
                'answer' => '$name = "Ada";',
                'description' => '第 3 行變數少了 $',
                'solo' => 2,
            ]);

            // 3. 實作題 01 (輸出 hello)
            Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => '輸出 hello',
                'type' => Question::TYPE_CODING,
                'question_content' => '請寫一段 PHP 程式，輸出 hello。',
                'bloom_id' => 'B31',
                'description' => '基礎輸出實作',
                'show_example' => false,
                'starter_code' => "<?php\n// 請在下方撰寫程式碼\n",
                'expected_output' => 'hello',
                'reference_answer' => "<?php\necho \"hello\";\n",
            ]);

            // 4. 陣列選擇題
            $q4 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP選擇01',
                'type' => Question::TYPE_CHOICE,
                'question_content' => '以下哪一個是正確的 PHP 陣列宣告？',
                'bloom_id' => 'B11',
                'description' => '記憶',
                'show_example' => false,
            ]);
            QuestionOption::create([
                'question_id' => $q4->id,
                'title' => '$arr = [1, 2, 3];',
                'description' => '正確簡短語法',
                'is_answer' => true,
                'solo' => 2,
            ]);
            QuestionOption::create([
                'question_id' => $q4->id,
                'title' => '$arr = (1, 2, 3);',
                'description' => '括號錯誤',
                'is_answer' => false,
                'solo' => 1,
            ]);

            // 5. 是非題 01
            $q5 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP是非01',
                'type' => Question::TYPE_TRUE_FALSE,
                'question_content' => 'PHP 使用 == 比較兩個變數時，會同時檢查資料型別',
                'bloom_id' => 'B13',
                'description' => '記憶',
                'show_example' => false,
            ]);
            QuestionOption::create([
                'question_id' => $q5->id,
                'title' => '是',
                'description' => '== 主要比較值；=== 才會同時比較值與型別',
                'is_answer' => false,
                'solo' => 1,
            ]);
            QuestionOption::create([
                'question_id' => $q5->id,
                'title' => '否',
                'description' => '== 主要比較值；=== 才會同時比較值與型別',
                'is_answer' => true,
                'solo' => 2,
            ]);

            // 6. 填空題
            $q7 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP填空01',
                'type' => Question::TYPE_FILL,
                'question_content' => 'PHP 程式碼通常以（1）作為結束標記',
                'bloom_id' => 'B33',
                'description' => '填空題',
                'show_example' => false,
            ]);
            QuestionSubAnswer::create([
                'question_id' => $q7->id,
                'sub_id' => 1,
                'answer' => ';',
                'description' => '分號結束',
                'solo' => 2,
            ]);

            // 7. 解讀題
            $q9 = Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP解讀01',
                'type' => Question::TYPE_INTERPRET,
                'question_content' => "請解讀以下 PHP 程式，說明最後會輸出什麼\n<!--code-stem-->\n\$numbers = [10, 20, 30];\necho \$numbers[1];",
                'bloom_id' => 'B51',
                'description' => '程式解讀',
                'show_example' => false,
            ]);
            QuestionSubAnswer::create([
                'question_id' => $q9->id,
                'sub_id' => 1,
                'answer' => '20',
                'description' => 'PHP 陣列索引從 0 開始，因此 $numbers[1] 為 20',
                'solo' => 2,
            ]);

            // 8. 實作題 02 (判斷偶數 - 即 SQL dump 中的 PHP實作01)
            Question::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'PHP實作01',
                'type' => Question::TYPE_CODING,
                'question_content' => '請使用 PHP 撰寫程式，判斷一個數字 $number 是否為偶數，若是請輸出「偶數」，否則輸出「奇數」。',
                'bloom_id' => 'B61',
                'description' => '流程控制實作題',
                'show_example' => false,
                'starter_code' => "<?php\n\$number = 8;\n// 請在下方撰寫判斷邏輯\n",
                'expected_output' => '偶數',
                'reference_answer' => "<?php\n\$number = 8;\nif (\$number % 2 == 0) {\n    echo \"偶數\";\n} else {\n    echo \"奇數\";\n}\n",
            ]);
        }
    }
}
