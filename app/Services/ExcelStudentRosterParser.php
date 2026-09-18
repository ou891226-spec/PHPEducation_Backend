<?php

namespace App\Services;

use InvalidArgumentException;
use ZipArchive;

/**
 * 學生名冊 Excel：欄位列的下一列為範本示範不讀，再下一列起學號、姓名。
 */
class ExcelStudentRosterParser
{
    /**
     * @return list<array{student_no: string, name: string}>
     */
    public function parse(string $path): array
    {
        $rows = $this->readRows($path);
        $headerIndex = $this->findHeaderRow($rows);
        if ($headerIndex === null) {
            throw new InvalidArgumentException('找不到欄位列（學號 / 姓名）');
        }

        $map = $this->mapColumns($rows[$headerIndex]);
        if (! isset($map['student_no'], $map['name'])) {
            throw new InvalidArgumentException('找不到欄位列（學號 / 姓名）');
        }

        $students = [];
        $seen = [];

        for ($i = $headerIndex + 2, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            $studentNo = $this->normalizeStudentNo($this->cell($row, $map['student_no']));
            $name = $this->cell($row, $map['name']);

            if ($studentNo === '' && $name === '') {
                continue;
            }

            if ($studentNo === '' || $name === '') {
                throw new InvalidArgumentException('學號與姓名都必須填寫');
            }

            if (isset($seen[$studentNo])) {
                throw new InvalidArgumentException('同一份名冊學號重複（'.$studentNo.'）');
            }

            $seen[$studentNo] = true;
            $students[] = [
                'student_no' => $studentNo,
                'name' => $name,
            ];
        }

        return $students;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $map = $this->mapColumns($row);
            if (isset($map['student_no'], $map['name'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function mapColumns(array $row): array
    {
        $map = [];

        foreach ($row as $column => $value) {
            $key = trim($value);
            if ($key === '學號' || str_starts_with($key, '學號')) {
                $map['student_no'] = $column;
            } elseif ($key === '姓名' || str_starts_with($key, '姓名')) {
                $map['name'] = $column;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function cell(array $row, ?string $column): string
    {
        if ($column === null) {
            return '';
        }

        return trim($row[$column] ?? '');
    }

    private function normalizeStudentNo(string $value): string
    {
        $value = preg_replace('/\s+/u', '', $value) ?? $value;
        if (preg_match('/^[sS](\d+)$/', $value, $matches) === 1) {
            return $matches[1];
        }

        return $value;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('無法開啟 Excel 檔');
        }

        $strings = $this->sharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            throw new InvalidArgumentException('Excel 沒有工作表');
        }

        $xml = simplexml_load_string($sheet);
        if ($xml === false) {
            throw new InvalidArgumentException('Excel 工作表格式錯誤');
        }

        $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $ref) ?? '';
                $cells[$column] = $this->cellValue($cell, $strings);
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sst = simplexml_load_string($xml);
        if ($sst === false) {
            return [];
        }

        $strings = [];
        foreach ($sst->si as $si) {
            $strings[] = trim(html_entity_decode(strip_tags($si->asXML() ?: ''), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        return $strings;
    }

    /**
     * @param  list<string>  $strings
     */
    private function cellValue(\SimpleXMLElement $cell, array $strings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->v;

            return $strings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            return trim((string) $cell->is->t);
        }

        return trim((string) $cell->v);
    }
}
