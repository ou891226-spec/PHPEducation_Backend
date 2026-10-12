{{ $studentName }} 同學您好：

您已加入以下課程：
@foreach ($courses as $course)
《{{ $course['name'] }}》（授課老師：{{ $course['teacher_name'] }}）
@endforeach

請登入 PHPEducation 查看課程內容。
