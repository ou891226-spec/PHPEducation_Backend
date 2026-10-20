您好，{{ $teacherName }} 老師：

我們收到重設您 PHPEducation 帳號（{{ $teacherAccount }}）密碼的申請。
請在 {{ $expiresInMinutes }} 分鐘內點擊以下連結設定新密碼：

<a href="{{ $resetUrl }}">{{ $resetUrl }}</a>

【安全提醒】
1. 連結只能使用一次，逾時請重新申請忘記密碼。
2. 若不是您本人申請，請忽略此信，原密碼仍可正常使用。
