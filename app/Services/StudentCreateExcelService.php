<?php
namespace App\Services;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentCreateExcelService {
    /**
     * 輸入：
     * 1. $students: 本次新建立的學生名單陣列（包含姓名、學號、初始密碼）
     * 2. $password: 教師的登入帳號（作為 Excel 解鎖密碼）
     * 讀取模板並產製學生初始帳號密碼 Excel 檔（二進位字串）
     *
     * @param  array<int, array{name: string, student_no: string, password: string}>  $students
     */
    public function generate(array $students, string $password): string
    {
        $templatePath = public_path('templates/student_account_template.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            // 如沒讀到Excel，則建立一個新的 Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('學生名單');
            $sheet->setCellValue('A1', '以下是學生開通的帳號及密碼，請妥善保存');
            $sheet->setCellValue('A2', '姓名');
            $sheet->setCellValue('B2', '帳號');
            $sheet->setCellValue('C2', '密碼');
        }
       
        // Excel 第3列開始的列數（不含說明列、標題列）。
        $row = 3;
        foreach ($students as $student) {
            $sheet->setCellValueExplicit('A' . $row, $student['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $student['student_no'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, $student['password'], DataType::TYPE_STRING);
            $row++;
        }
        
        // 設定 Excel 保護密碼
        if ($password !== '') {
            $sheet->getProtection()->setPassword($password);
            $sheet->getProtection()->setSheet(true);
            $sheet->getProtection()->setSort(true);
            $sheet->getProtection()->setInsertRows(true);
            $sheet->getProtection()->setFormatCells(true);
        }

        // 將 Spreadsheet 轉換為二進位字串
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
    }
}
