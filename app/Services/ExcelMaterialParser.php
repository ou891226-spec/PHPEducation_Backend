<?php

namespace App\Services;

use InvalidArgumentException;
use ZipArchive;

/**
 * 依 course_template.xlsx 欄位組成教材樹：Chapter → Unit → Knowledge Card。
 * 教材屬於目前課程，不從 Excel 讀主題。
 * 第 1 列欄位、第 2 列公版範例整列不讀，第 3 列起才是教材。空白章節／單元沿用上一列。
 */
class ExcelMaterialParser
{
    /**
     * @return array{chapters: list<array<string, mixed>>}
     */
    public function parse(string $path): array
    {
        $rows = $this->readRows($path);
        if ($rows === []) {
            throw new InvalidArgumentException('找不到教材內容');
        }

        $headerIndex = $this->findHeaderRow($rows);
        if ($headerIndex === null) {
            throw new InvalidArgumentException('找不到欄位列（chapter_title / unit_title / card_name）');
        }

        $map = $this->mapColumns($rows[$headerIndex]);
        if (! isset($map['chapter_title'], $map['unit_title'], $map['card_name'])) {
            throw new InvalidArgumentException('找不到欄位列（chapter_title / unit_title / card_name）');
        }

        $chapters = [];
        $lastChapter = '';
        $lastUnit = '';
        $lastChapterOrder = null;
        $lastUnitOrder = null;

        for ($i = $headerIndex + 1, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            if ($this->isExampleRow($row)) {
                continue;
            }

            $chapter = $this->cell($row, $map['chapter_title']);
            $unit = $this->cell($row, $map['unit_title']);
            $chapterOrder = $this->intCell($row, $map['chapter_order'] ?? null);
            $unitOrder = $this->intCell($row, $map['unit_order'] ?? null);
            $title = $this->cell($row, $map['card_name']);
            $type = $this->cell($row, $map['card_type'] ?? null);
            $content = $this->cell($row, $map['card_content'] ?? null);
            $example = $this->cell($row, $map['code_example'] ?? null);

            if ($chapter !== '') {
                $lastChapter = $chapter;
                $lastChapterOrder = $chapterOrder;
            } else {
                $chapter = $lastChapter;
                $chapterOrder = $lastChapterOrder;
            }

            if ($unit !== '') {
                $lastUnit = $unit;
                $lastUnitOrder = $unitOrder;
            } else {
                $unit = $lastUnit;
                $unitOrder = $lastUnitOrder;
            }

            if ($chapter === '') {
                continue;
            }

            $this->ensureChapter($chapters, $chapter, $chapterOrder);

            if ($unit === '') {
                continue;
            }

            $this->ensureUnit($chapters, $chapter, $unit, $unitOrder);

            if ($title === '') {
                continue;
            }

            $this->appendCard(
                $chapters,
                $chapter,
                $unit,
                $title,
                $type !== '' ? $type : 'keyword',
                $content,
                $example !== '' ? $example : null,
            );
        }

        $chapters = $this->finalizeChapters($chapters);
        if ($chapters === []) {
            throw new InvalidArgumentException('沒有可匯入的教材列');
        }

        return [
            'chapters' => $chapters,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $chapters
     * @return list<array<string, mixed>>
     */
    private function finalizeChapters(array $chapters): array
    {
        $list = [];
        $chapterOrder = 1;

        foreach ($chapters as $chapter) {
            $units = [];
            $unitOrder = 1;
            foreach ($chapter['units'] as $unit) {
                $unit['sort_order'] = $unit['sort_order'] ?: $unitOrder;
                $unitOrder = max($unitOrder, $unit['sort_order']) + 1;
                $units[] = $unit;
            }
            usort($units, fn (array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);
            $chapter['units'] = $units;
            $chapter['sort_order'] = $chapter['sort_order'] ?: $chapterOrder;
            $chapterOrder = max($chapterOrder, $chapter['sort_order']) + 1;
            $list[] = $chapter;
        }

        usort($list, fn (array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);

        return $list;
    }

    /**
     * @param  array<string, array<string, mixed>>  $chapters
     */
    private function ensureChapter(array &$chapters, string $chapter, ?int $order): void
    {
        if (! isset($chapters[$chapter])) {
            $chapters[$chapter] = $this->namedNode($chapter, 'units');
        }
        if ($order !== null) {
            $chapters[$chapter]['sort_order'] = $order;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $chapters
     */
    private function ensureUnit(array &$chapters, string $chapter, string $unit, ?int $order): void
    {
        $this->ensureChapter($chapters, $chapter, null);
        $units = &$chapters[$chapter]['units'];
        if (! isset($units[$unit])) {
            $units[$unit] = $this->namedNode($unit, 'knowledge_cards');
        }
        if ($order !== null) {
            $units[$unit]['sort_order'] = $order;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $chapters
     */
    private function appendCard(
        array &$chapters,
        string $chapter,
        string $unit,
        string $title,
        string $type,
        string $content,
        ?string $example,
    ): void {
        $this->ensureUnit($chapters, $chapter, $unit, null);
        $cards = &$chapters[$chapter]['units'][$unit]['knowledge_cards'];

        foreach ($cards as &$existing) {
            if (($existing['title'] ?? '') !== $title || ($existing['type'] ?? '') !== $type) {
                continue;
            }
            if ($content !== '') {
                $existing['content'] = trim($existing['content'] === '' ? $content : $existing['content']."\n\n".$content);
            }
            if ($example) {
                $existing['example'] = $existing['example']
                    ? $existing['example']."\n\n".$example
                    : $example;
            }

            return;
        }
        unset($existing);

        $cards[] = [
            'title' => $title,
            'type' => $type,
            'content' => $content,
            'example' => $example,
            'sort_order' => count($cards) + 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function namedNode(string $name, string $childKey): array
    {
        return [
            'name' => $name,
            'sort_order' => 0,
            $childKey => [],
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $joined = implode(' ', array_map(fn (string $value) => mb_strtolower(trim($value)), $row));
            $hasChapter = str_contains($joined, 'chapter_title') || str_contains($joined, '章節');
            $hasUnit = str_contains($joined, 'unit_title') || str_contains($joined, '單元');
            if ($hasChapter && $hasUnit) {
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
            $key = mb_strtolower(trim($value));
            if ($key === 'chapter_title' || $key === '章節') {
                $map['chapter_title'] = $column;
            } elseif ($key === 'chapter_order') {
                $map['chapter_order'] = $column;
            } elseif ($key === 'unit_title' || $key === '單元') {
                $map['unit_title'] = $column;
            } elseif ($key === 'unit_order') {
                $map['unit_order'] = $column;
            } elseif ($key === 'card_name' || str_contains($key, '知識卡標題')) {
                $map['card_name'] = $column;
            } elseif ($key === 'card_type' || $key === '類型') {
                $map['card_type'] = $column;
            } elseif ($key === 'card_content' || str_contains($key, '知識卡內容')) {
                $map['card_content'] = $column;
            } elseif ($key === 'code_example' || $key === '範例' || str_starts_with($key, '範例')) {
                $map['code_example'] = $column;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isExampleRow(array $row): bool
    {
        $values = array_filter(array_map('trim', array_values($row)), fn (string $value) => $value !== '');
        if ($values === []) {
            return true;
        }

        foreach ($values as $value) {
            if (str_starts_with(mb_strtolower($value), 'ex：') || str_starts_with(mb_strtolower($value), 'ex:')) {
                return true;
            }
        }

        return false;
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

    /**
     * @param  array<string, string>  $row
     */
    private function intCell(array $row, ?string $column): ?int
    {
        $value = $this->cell($row, $column);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
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
