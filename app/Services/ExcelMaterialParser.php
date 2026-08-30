<?php

namespace App\Services;

use App\Support\KnowledgeConcept;
use InvalidArgumentException;
use ZipArchive;

/**
 * 依 Excel 欄位組成教材樹：Chapter → Unit（知識點）→ Knowledge Card + example。
 * 主題由網頁匯入時一次填寫，不從 Excel 讀 topics 欄。
 * 欄位列的下一列是範本示範，整列不讀；再下一列起才是正式資料。
 * 「說明」列是內容，「實作變數01」列併進同章知識卡的 example，不單獨當知識點。
 */
class ExcelMaterialParser
{
    /**
     * @return array{name: string, topics: list<array<string, mixed>>}
     */
    public function parse(string $path, string $topicName): array
    {
        $topicName = trim($topicName);
        if ($topicName === '') {
            throw new InvalidArgumentException('請填寫主題名稱');
        }

        $rows = $this->readRows($path);
        if ($rows === []) {
            throw new InvalidArgumentException('找不到教材名稱');
        }

        $headerIndex = $this->findHeaderRow($rows);
        if ($headerIndex === null) {
            throw new InvalidArgumentException('找不到欄位列（chapters / units / knowledge_card）');
        }

        $name = $this->findMaterialName($rows, $headerIndex);
        $map = $this->mapColumns($rows[$headerIndex]);
        $topics = [];
        $lastChapter = '';
        $lastUnit = '';
        $pending = [];

        // 欄位列的下一列是範本示範，整列不讀；再下一列起 knowledge_card + example
        for ($i = $headerIndex + 2, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            $example = $this->cell($row, $map['example'] ?? null);
            $chapter = $this->cell($row, $map['chapters'] ?? null);
            $unit = $this->cell($row, $map['units'] ?? null);
            $content = $this->cell($row, $map['knowledge_card'] ?? null);
            $titleOverride = $this->cell($row, $map['title'] ?? null);

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

            if ($chapter === '' || $unit === '' || ($content === '' && $example === '' && $titleOverride === '')) {
                continue;
            }

            $pending[] = [
                'chapter' => $chapter,
                'unit' => $unit,
                'content' => $content,
                'example' => $example,
                'title' => $titleOverride,
            ];
        }

        $this->appendNormalizedCards($topics, $topicName, $pending);

        $topics = $this->finalizeTopics($topics);
        if ($name === '' && $topics !== []) {
            $name = $topicName;
        }
        if ($name === '') {
            throw new InvalidArgumentException('找不到教材名稱');
        }

        return [
            'name' => $name,
            'topics' => $topics,
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
     * @param  list<array{chapter: string, unit: string, content: string, example: string, title: string}>  $pending
     */
    private function appendNormalizedCards(array &$topics, string $topicName, array $pending): void
    {
        $byChapter = [];
        foreach ($pending as $row) {
            $byChapter[$row['chapter']][] = $row;
        }

        foreach ($byChapter as $chapterName => $rows) {
            $fallback = $this->fallbackConcept($rows);

            foreach ($rows as $row) {
                $concept = trim($row['title'])
                    ?: KnowledgeConcept::fromUnitName($row['unit'])
                    ?: $fallback
                    ?: $this->titleFromContent($row['content'] !== '' ? $row['content'] : $row['example']);

                if ($concept === '') {
                    continue;
                }

                $content = $row['content'];
                $example = $row['example'];
                if (KnowledgeConcept::isPracticeSection($row['unit'])) {
                    if ($example === '') {
                        $example = $content;
                        $content = '';
                    } elseif ($this->looksLikeCode($content)) {
                        $content = '';
                    }
                }

                $this->appendCard($topics, $topicName, $chapterName, $concept, $content, $example, $concept);
            }
        }
    }

    /**
     * @param  list<array{chapter: string, unit: string, content: string, example: string, title: string}>  $rows
     */
    private function fallbackConcept(array $rows): ?string
    {
        $named = [];
        $practice = [];

        foreach ($rows as $row) {
            $fromUnit = KnowledgeConcept::fromUnitName($row['unit']);
            if ($fromUnit === null) {
                continue;
            }
            if (KnowledgeConcept::isPracticeSection($row['unit'])) {
                $practice[$fromUnit] = true;
            } else {
                $named[$fromUnit] = true;
            }
        }

        $namedList = array_keys($named);
        $practiceList = array_keys($practice);

        if (count($namedList) === 1) {
            return $namedList[0];
        }
        if (count($practiceList) === 1) {
            return $practiceList[0];
        }
        if (count($practiceList) > 1) {
            return $practiceList[0];
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $topics
     */
    private function appendCard(
        array &$topics,
        string $topic,
        string $chapter,
        string $unit,
        string $content,
        string $example = '',
        ?string $title = null,
    ): void {
        $title = trim((string) ($title ?? $unit));
        if ($title === '') {
            return;
        }

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

        foreach ($units[$unit]['knowledge_cards'] as &$existing) {
            if (($existing['title'] ?? '') !== $title) {
                continue;
            }
            if ($content !== '') {
                $existing['content'] = trim($existing['content'] === '' ? $content : $existing['content']."\n\n".$content);
            }
            if ($example !== '') {
                $existing['example'] = $existing['example']
                    ? $existing['example']."\n\n".$example
                    : $example;
            }

            return;
        }
        unset($existing);

        $units[$unit]['knowledge_cards'][] = [
            'id' => $this->newId(),
            'title' => $title,
            'content' => $content !== '' ? $content : $title,
            'example' => $example !== '' ? $example : null,
            'sort_order' => count($units[$unit]['knowledge_cards']) + 1,
        ];
    }

    private function looksLikeCode(string $text): bool
    {
        $trim = ltrim($text);

        return str_starts_with($trim, '<?')
            || str_starts_with($trim, '$')
            || str_starts_with($trim, '//')
            || str_starts_with($trim, '/*')
            || str_starts_with($trim, '#');
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

        return '';
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $joined = implode(' ', array_map(fn (string $value) => mb_strtolower(trim($value)), $row));
            $hasChapters = str_contains($joined, 'chapters') || str_contains($joined, '章節');
            $hasUnits = str_contains($joined, 'units') || str_contains($joined, '單元');
            if ($hasChapters && $hasUnits) {
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
            if (str_starts_with($key, 'chapters') || $key === '章節') {
                $map['chapters'] = $column;
            } elseif (str_starts_with($key, 'units') || $key === '單元') {
                $map['units'] = $column;
            } elseif (
                $key === 'title'
                || str_contains($key, '知識點')
                || str_contains($key, '知識卡標題')
                || str_contains($key, 'card_title')
            ) {
                $map['title'] = $column;
            } elseif (str_contains($key, 'knowledge_card') || str_starts_with($key, '知識卡')) {
                $map['knowledge_card'] = $column;
            } elseif ($key === '範例' || str_starts_with($key, '範例')) {
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
