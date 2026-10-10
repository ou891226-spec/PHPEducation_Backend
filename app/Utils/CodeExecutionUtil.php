<?php

namespace App\Utils;

use Illuminate\Support\Facades\Process;
use Illuminate\Process\Exceptions\ProcessTimedOutException;

/**
 * 程式碼執行沙盒工具類別
 * 
 * 負責將傳入的 PHP 程式碼寫入暫存檔，透過命令列 Process 直譯執行，
 * 並即時捕捉標準輸出 (STDOUT)、標準錯誤 (STDERR)、退出狀態碼與超時狀態
 */
class CodeExecutionUtil
{
    /**
     * 執行 PHP 程式碼並傳入標準輸入
     *
     * @param  string|null  $code   學生撰寫的 PHP 程式碼
     * @param  string|null  $input  欲透過 STDIN 傳入的測資字串 (選填)
     * @return array 執行結果陣列：
     *         - 'success'  => bool (是否執行成功，exitCode === 0)
     *         - 'output'   => string (標準輸出內容，已去除首尾空白)
     *         - 'error'    => string (標準錯誤訊息，如 Parse Error 等)
     *         - 'exitCode' => int|null (程序結束代碼)
     *         - 'timeout'  => bool (是否因超過 5 秒而被強制中止)
     */
    public static function execute(
        ?string $code,
        ?string $input = '',
    ): array {
        // 確保參數為字串，防止 null 引發例外
        $code = (string) ($code ?? '');
        $input = (string) ($input ?? '');

        // 防呆檢查：未輸入任何程式碼
        if (trim($code) === '') {
            return [
                'success' => false,
                'output' => '',
                'error' => '請輸入程式碼',
                'exitCode' => 1,
                'timeout' => false,
            ];
        }

        // 自動補齊：若程式碼未帶 <?php 開頭標籤，自動在最前面加入，避免 CLI 原樣輸出純文字
        if (!str_contains($code, '<?php')) {
            $code = "<?php\n" . $code;
        }

        // 動態生成獨立暫存檔案路徑 (例如: storage/app/tmp/code_64df...php)
        $filePath = storage_path('app/tmp/code_' . uniqid() . '.php');

        // 確保暫存目錄存在
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // 將處理後的程式碼寫入暫存檔
        file_put_contents($filePath, $code);

        try {
            // 透過 Process 模組呼叫 php 指令執行，設定超時限制為 5 秒
            $result = Process::timeout(5)->input($input)->run(['php', $filePath]);
            
            return [
                'success' => $result->successful(),
                'output' => trim($result->output()),
                'error' => trim($result->errorOutput()),
                'exitCode' => $result->exitCode(),
                'timeout' => false,
            ];
        } catch (ProcessTimedOutException $e) {
            // 捕捉執行超時例外 (如無窮迴圈 while(true))
            return [
                'success' => false,
                'output' => '',
                'error' => '程式執行超時（超過 5 秒限制）',
                'exitCode' => null,
                'timeout' => true,
            ];
        } finally {
            // 不管執行成功、失敗或超時，最後都一定會刪除暫存檔案，保持系統整潔
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
