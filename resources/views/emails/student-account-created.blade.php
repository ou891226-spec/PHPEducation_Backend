{{ $studentName }} 同學您好：

您的 PHPEducation 學生帳號已開通。
帳號：{{ $studentAccount }}
初始密碼：{{ $initialPassword }}
@if (! empty($courses))

您已加入以下課程：
@foreach ($courses as $course)
《{{ $course['name'] }}》（授課老師：{{ $course['teacher_name'] }}）
@endforeach
@endif

登入時帳號請填學號。登入後請盡快修改密碼；忘記密碼可在登入頁點「忘記密碼」重設。
