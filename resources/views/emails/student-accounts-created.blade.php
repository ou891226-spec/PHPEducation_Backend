您好，{{ $teacherName }} 老師：

您所提交的學生批次帳號申請已審核通過。

班級：{{ $className }}

本次共建立 {{ count($students) }} 位學生帳號。

@foreach ($students as $student)

學生姓名：{{ $student['name'] }}
學號：{{ $student['student_no'] }}
初始密碼：{{ $student['password'] }}

@endforeach

請將以上帳號資訊提供給對應學生使用。