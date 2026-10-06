您好，{{ $teacherName }} 老師：

您為「{{ $courseName }}/{{ $className }}」提交的課程申請，管理員已審核開通。
本次新建帳號 {{ $studentCount }} 人，姓名、帳號與初始密碼詳見加密附件。
請妥善保存，並將帳密轉交給對應學生。
@if ($joinedCount > 0)
另有 {{ $joinedCount }} 位學生已有帳號，已直接加入課程，請使用原本的密碼登入。
@endif

請勿將附件轉寄給無關人員。學生登入時，帳號請填學號。
