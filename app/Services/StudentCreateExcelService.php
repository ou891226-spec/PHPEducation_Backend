<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 學生帳號密碼 Excel 產生服務
 * 
 * 負責將新開通的學生帳密清單填入 Excel 模板，並以教師帳號作為密碼加密保護工作表。
 */
class StudentCreateExcelService
{
    /**
     * 讀取模板並產製學生初始帳號密碼 Excel 檔（回傳二進位字串供 Email 附件使用）
     *
     * @param  array<int, array{name: string, student_no: string, password: string}>  $students 本次新建立的學生名單陣列
     * @param  string  $password 教師的登入帳號（作為 Excel 工作表解鎖保護密碼）
     * @return string Excel 檔案之二進位二進制資料字串
     */
    public function generate(array $students, string $password): string
    {
        $templatePath = public_path('templates/student_account_template.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            // 如未找到實體模板檔案，則建立備用 Spreadsheet 結構
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('學生名單');
            $sheet->setCellValue('A1', '以下是學生開通的帳號及密碼，請妥善保存');
            $sheet->setCellValue('A2', '姓名');
            $sheet->setCellValue('B2', '帳號');
            $sheet->setCellValue('C2', '密碼');
        }
       
        // 資料從 Excel 第 3 列開始寫入（第 1 列為說明，第 2 列為標題）
        $row = 3;
        foreach ($students as $student) {
            $sheet->setCellValueExplicit('A' . $row, $student['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $student['student_no'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, $student['password'], DataType::TYPE_STRING);
            $row++;
        }
        
        // 設定 Excel 工作表保護密碼（防止學生名單與密碼被未授權人員隨意編輯或查閱）
        if ($password !== '') {
            $sheet->getProtection()->setPassword($password);
            $sheet->getProtection()->setSheet(true);
            $sheet->getProtection()->setSort(true);
            $sheet->getProtection()->setInsertRows(true);
            $sheet->getProtection()->setFormatCells(true);
        }

        // 將 Spreadsheet 轉換為二進位字串輸出
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
    }
}
