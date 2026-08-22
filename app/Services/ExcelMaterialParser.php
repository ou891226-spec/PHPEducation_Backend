<?php

namespace App\Services;

use InvalidArgumentException;
use ZipArchive;

/**
 * 依 Excel 欄位組成教材樹：教材名稱 + Topic → Chapter → Unit → Knowledge Card。
 * 欄位列的下一列是範例資料，整列不讀；之後「範例」欄有值的列也不匯入。
 */
class ExcelMaterialParser
{
    /**
     * @return array{name: string, topics: list<array<string, mixed>>}
     */
    public function parse(string $path): array
    {
        $rows = $this->readRows($path);
        if ($rows === []) {
            throw new InvalidArgumentException('找不到教材名稱');
        }

        $headerIndex = $this->findHeaderRow($rows);
        if ($headerIndex === null) {
            throw new InvalidArgumentException('找不到欄位列（topics / chapters / units / knowledge_card）');
        }

        $name = $this->findMaterialName($rows, $headerIndex);
        $map = $this->mapColumns($rows[$headerIndex]);
        $topics = [];
        $lastTopic = '';
        $lastChapter = '';
        $lastUnit = '';

        // 欄位列的下一列是範例資料，從再下一列開始讀教師內容
        for ($i = $headerIndex + 2, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            $example = $this->cell($row, $map['example'] ?? null);
            if ($example !== '') {
                continue;
            }

            $topic = $this->cell($row, $map['topics'] ?? null);
            $chapter = $this->cell($row, $map['chapters'] ?? null);
            $unit = $this->cell($row, $map['units'] ?? null);
            $content = $this->cell($row, $map['knowledge_card'] ?? null);

            if ($topic !== '') {
                $lastTopic = $topic;
            } else {
                $topic = $lastTopic;
            }

            if ($chapter !== '') {
                $lastChapter = $chapter;
            } else {
                $chapter = $lastChapter;
            }

            if ($unit !== '') {
                $lastUnit = $unit;
            } else {
                $unit = $lastUnit;
            }

            if ($topic === '' || $chapter === '' || $unit === '' || $content === '') {
                continue;
            }

            $this->appendCard($topics, $topic, $chapter, $unit, $content);
        }

        return [
            'name' => $name,
            'topics' => $this->finalizeTopics($topics),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $topics
     * @return list<array<string, mixed>>
     */
    private function finalizeTopics(array $topics): array
    {
        $list = [];
        $topicOrder = 1;

        foreach ($topics as $topic) {
            $chapters = [];
            $chapterOrder = 1;
            foreach ($topic['chapters'] as $chapter) {
                $units = [];
                $unitOrder = 1;
                foreach ($chapter['units'] as $unit) {
                    $unit['sort_order'] = $unitOrder++;
                    $units[] = $unit;
                }
                $chapter['units'] = $units;
                $chapter['sort_order'] = $chapterOrder++;
                $chapters[] = $chapter;
            }
            $topic['chapters'] = $chapters;
            $topic['sort_order'] = $topicOrder++;
            $list[] = $topic;
        }

        return $list;
    }

    /**
     * @param  array<string, array<string, mixed>>  $topics
     */
    private function appendCard(array &$topics, string $topic, string $chapter, string $unit, string $content): void
    {
        if (! isset($topics[$topic])) {
            $topics[$topic] = $this->namedNode($topic, 'chapters');
        }

        $chapters = &$topics[$topic]['chapters'];
        if (! isset($chapters[$chapter])) {
            $chapters[$chapter] = $this->namedNode($chapter, 'units');
        }

        $units = &$chapters[$chapter]['units'];
        if (! isset($units[$unit])) {
            $units[$unit] = $this->namedNode($unit, 'knowledge_cards');
        }

        $units[$unit]['knowledge_cards'][] = [
            'id' => $this->newId(),
            'title' => $this->titleFromContent($content),
            'content' => $content,
            'sort_order' => count($units[$unit]['knowledge_cards']) + 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function namedNode(string $name, string $childKey): array
    {
        return [
            'id' => $this->newId(),
            'name' => $name,
            'sort_order' => 0,
            $childKey => [],
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function findMaterialName(array $rows, int $headerIndex): string
    {
        for ($i = 0; $i < $headerIndex; $i++) {
            foreach ($rows[$i] as $column => $value) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }

                if (preg_match('/^教材名稱\s*[:：]\s*(.+)$/u', $value, $matches) === 1) {
                    return trim($matches[1]);
                }

                if (preg_match('/^教材名稱\s*[:：]?\s*$/u', $value) === 1) {
                    $nextColumn = $column;
                    $nextColumn++;
                    $next = trim($rows[$i][$nextColumn] ?? '');
                    $next = preg_replace('/^[:：]\s*/u', '', $next) ?? $next;
                    if (trim($next) !== '') {
                        return trim($next);
                    }
                }
            }
        }

        throw new InvalidArgumentException('找不到教材名稱');
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $joined = implode(' ', array_map(fn (string $value) => mb_strtolower(trim($value)), $row));
            if (str_contains($joined, 'topics') && str_contains($joined, 'chapters') && str_contains($joined, 'units')) {
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
            if ($key === 'topics') {
                $map['topics'] = $column;
            } elseif ($key === 'chapters') {
                $map['chapters'] = $column;
            } elseif ($key === 'units') {
                $map['units'] = $column;
            } elseif (str_contains($key, 'knowledge_card')) {
                $map['knowledge_card'] = $column;
            } elseif ($key === '範例') {
                $map['example'] = $column;
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

        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
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

    private function titleFromContent(string $content): string
    {
        $line = trim(strtok(str_replace(["\r\n", "\r"], "\n", $content), "\n") ?: $content);
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;

        return mb_substr($line, 0, 80);
    }

    private function newId(): string
    {
        return (string) \Illuminate\Support\Str::ulid();
    }
}
