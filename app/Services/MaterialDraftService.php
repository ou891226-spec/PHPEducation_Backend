<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\KnowledgeCard;
use App\Models\MaterialDraft;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 教師教材草稿：Excel 組成 tree → 網頁改 tree → 發布後才寫入正式表給學生看。
 */
class MaterialDraftService
{
    public function __construct(
        private readonly ExcelMaterialParser $parser,
        private readonly CourseService $courseService,
    ) {}

    /**
     * Excel 匯入成一份新 Draft（status = draft）。
     * 主題名稱來自網頁表單；教材名稱來自 Excel 最上方，與 Topic 名稱不是同一件事。
     *
     * @return array<string, mixed>
     */
    public function import(Teacher $teacher, int $courseId, string $path, string $topicName): array
    {
        $this->courseService->findForTeacher($teacher, $courseId);

        try {
            $parsed = $this->parser->parse($path, $topicName);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }
        $topics = $parsed['topics'];
        if ($topics === []) {
            throw ValidationException::withMessages([
                'file' => ['沒有可匯入的教材列（第 3 列範本不讀）'],
            ]);
        }

        // 同一份 Draft 內 Topic 不可重名；未發布草稿的教材名稱不可重複
        $this->assertUniqueTopicNames($this->topicNames($topics), 'file');
        $this->assertMaterialNameAvailable($courseId, $parsed['name']);

        $draft = MaterialDraft::query()->create([
            'course_id' => $courseId,
            'teacher_id' => $teacher->id,
            'name' => $parsed['name'],
            'status' => MaterialDraft::STATUS_DRAFT,
            'tree' => [
                'topics' => $topics,
            ],
        ]);

        return $this->formatDraft($draft);
    }

    /**
     * 列出該課所有 Draft（含 draft / published / archived）。
     *
     * @return list<array<string, mixed>>
     */
    public function listForCourse(Teacher $teacher, int $courseId): array
    {
        $this->courseService->findForTeacher($teacher, $courseId);

        return MaterialDraft::query()
            ->where('course_id', $courseId)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MaterialDraft $draft) => $this->formatDraft($draft))
            ->all();
    }

    /**
     * 從目前已發布教材複製一份新的可編輯 Draft。已發布那份維持 published。
     * 有傳 topic_id 時只複製那一個主題（對應前端「加入草稿編輯」）。
     *
     * @return array<string, mixed>
     */
    public function createFromPublished(Teacher $teacher, int $courseId, ?int $topicId = null): array
    {
        $this->courseService->findForTeacher($teacher, $courseId);

        $published = MaterialDraft::query()
            ->where('course_id', $courseId)
            ->where('status', MaterialDraft::STATUS_PUBLISHED)
            ->first();

        $topics = $this->treeFromOfficial($courseId, $topicId);

        if ($topics === []) {
            throw new ModelNotFoundException();
        }

        $draftName = $topicId !== null
            ? (string) $topics[0]['name']
            : ($published !== null && trim((string) $published->name) !== ''
                ? $published->name
                : '正式教材');

        $draft = MaterialDraft::query()->create([
            'course_id' => $courseId,
            'teacher_id' => $teacher->id,
            'name' => $draftName,
            'status' => MaterialDraft::STATUS_DRAFT,
            'tree' => [
                'topics' => $topics,
            ],
        ]);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿新增主題。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function addTopic(Teacher $teacher, int $draftId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $this->assertUniqueTopicNames(
            [...$this->topicNames($tree['topics']), $data['name']],
            'name',
        );
        $tree['topics'][] = $this->namedNode($data['name'], 'chapters', $data['sort_order'] ?? null, count($tree['topics']));
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿修改主題。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function updateTopic(Teacher $teacher, int $draftId, string $nodeId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $topic = &$this->findTopic($tree, $nodeId);
        $names = [];
        foreach ($tree['topics'] as $node) {
            $names[] = $node['id'] === $nodeId ? $data['name'] : $node['name'];
        }
        $this->assertUniqueTopicNames($names, 'name');
        $topic['name'] = $data['name'];
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $topic['sort_order'] = $data['sort_order'];
        }
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿刪除主題。
     *
     * @return array<string, mixed>
     */
    public function deleteTopic(Teacher $teacher, int $draftId, string $nodeId): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $tree['topics'] = array_values(array_filter(
            $tree['topics'],
            fn (array $topic) => $topic['id'] !== $nodeId,
        ));
        $this->saveTree($draft, $tree);

        $payload = $this->formatDraft($draft);
        if ($tree['topics'] === []) {
            $draft->delete();
        }

        return $payload;
    }

    /**
     * 草稿新增章節。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function addChapter(Teacher $teacher, int $draftId, string $topicId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $topic = &$this->findTopic($tree, $topicId);
        $topic['chapters'][] = $this->namedNode($data['name'], 'units', $data['sort_order'] ?? null, count($topic['chapters']));
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿修改章節。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function updateChapter(Teacher $teacher, int $draftId, string $nodeId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $chapter = &$this->findChapter($tree, $nodeId);
        $chapter['name'] = $data['name'];
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $chapter['sort_order'] = $data['sort_order'];
        }
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿刪除章節。
     *
     * @return array<string, mixed>
     */
    public function deleteChapter(Teacher $teacher, int $draftId, string $nodeId): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        foreach ($tree['topics'] as &$topic) {
            $topic['chapters'] = array_values(array_filter(
                $topic['chapters'],
                fn (array $chapter) => $chapter['id'] !== $nodeId,
            ));
        }
        unset($topic);
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿新增單元。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function addUnit(Teacher $teacher, int $draftId, string $chapterId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $chapter = &$this->findChapter($tree, $chapterId);
        $chapter['units'][] = $this->namedNode($data['name'], 'knowledge_cards', $data['sort_order'] ?? null, count($chapter['units']));
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿修改單元。
     *
     * @param  array{name: string, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function updateUnit(Teacher $teacher, int $draftId, string $nodeId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $unit = &$this->findUnit($tree, $nodeId);
        $unit['name'] = $data['name'];
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $unit['sort_order'] = $data['sort_order'];
        }
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿刪除單元。
     *
     * @return array<string, mixed>
     */
    public function deleteUnit(Teacher $teacher, int $draftId, string $nodeId): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        foreach ($tree['topics'] as &$topic) {
            foreach ($topic['chapters'] as &$chapter) {
                $chapter['units'] = array_values(array_filter(
                    $chapter['units'],
                    fn (array $unit) => $unit['id'] !== $nodeId,
                ));
            }
        }
        unset($topic, $chapter);
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿新增知識卡。
     *
     * @param  array{title: string, content: string, example?: string|null, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function addCard(Teacher $teacher, int $draftId, string $unitId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $unit = &$this->findUnit($tree, $unitId);
        $unit['knowledge_cards'][] = [
            'id' => (string) Str::ulid(),
            'title' => $data['title'],
            'content' => $data['content'],
            'example' => $data['example'] ?? null,
            'sort_order' => $data['sort_order'] ?? count($unit['knowledge_cards']) + 1,
        ];
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿修改知識卡。
     *
     * @param  array{title: string, content: string, example?: string|null, sort_order?: int|null}  $data
     * @return array<string, mixed>
     */
    public function updateCard(Teacher $teacher, int $draftId, string $nodeId, array $data): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        $card = &$this->findCard($tree, $nodeId);
        $card['title'] = $data['title'];
        $card['content'] = $data['content'];
        $card['example'] = $data['example'] ?? null;
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $card['sort_order'] = $data['sort_order'];
        }
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 草稿刪除知識卡。
     *
     * @return array<string, mixed>
     */
    public function deleteCard(Teacher $teacher, int $draftId, string $nodeId): array
    {
        $draft = $this->editableDraft($teacher, $draftId);
        $tree = $this->tree($draft);
        foreach ($tree['topics'] as &$topic) {
            foreach ($topic['chapters'] as &$chapter) {
                foreach ($chapter['units'] as &$unit) {
                    $unit['knowledge_cards'] = array_values(array_filter(
                        $unit['knowledge_cards'],
                        fn (array $card) => $card['id'] !== $nodeId,
                    ));
                }
            }
        }
        unset($topic, $chapter, $unit);
        $this->saveTree($draft, $tree);

        return $this->formatDraft($draft);
    }

    /**
     * 發布 Draft：只同步這份 tree 裡的主題，其他已發布主題不動。
     * 知識卡盡量 update 以保留 id，避免 question_knowledge_cards 斷掉。
     *
     * @return array<string, mixed>
     */
    public function publish(Teacher $teacher, int $draftId): array
    {
        $draft = $this->ownedDraft($teacher, $draftId);
        if (! $draft->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['請先進入編輯再開發布'],
            ]);
        }

        $tree = $this->tree($draft);
        $this->assertUniqueTopicNames($this->topicNames($tree['topics']), 'name');

        DB::transaction(function () use ($draft, $tree): void {
            MaterialDraft::query()
                ->where('course_id', $draft->course_id)
                ->where('status', MaterialDraft::STATUS_PUBLISHED)
                ->whereKeyNot($draft->id)
                ->update(['status' => MaterialDraft::STATUS_ARCHIVED]);

            foreach ($tree['topics'] as $topicNode) {
                $this->syncOfficialTopic((int) $draft->course_id, $topicNode);
            }

            $draft->update([
                'status' => MaterialDraft::STATUS_PUBLISHED,
                'tree' => [
                    'topics' => $tree['topics'],
                ],
            ]);
        });

        return $this->formatDraft($draft->fresh());
    }

    /**
     * Draft API 回傳格式。
     *
     * @return array<string, mixed>
     */
    public function formatDraft(MaterialDraft $draft): array
    {
        $tree = $this->tree($draft);

        return [
            'id' => $draft->id,
            'course_id' => $draft->course_id,
            'name' => $draft->name,
            'status' => $draft->status,
            'topics' => $tree['topics'],
            'created_at' => $draft->created_at,
            'updated_at' => $draft->updated_at,
        ];
    }

    /**
     * 取出 Draft 的 tree，並把 topics 重排成 0、1、2…
     *
     * @return array{topics: list<array<string, mixed>>}
     */
    private function tree(MaterialDraft $draft): array
    {
        $tree = $draft->tree ?? ['topics' => []];
        $tree['topics'] = array_values($tree['topics'] ?? []);

        return $tree;
    }

    /** 把改過的 tree 存回 Draft。 */
    private function saveTree(MaterialDraft $draft, array $tree): void
    {
        $tree['topics'] = array_values($tree['topics'] ?? []);
        $draft->update(['tree' => $tree]);
    }

    /** 只有 status = draft 才能改／再發布；published、archived 不行。 */
    private function editableDraft(Teacher $teacher, int $draftId): MaterialDraft
    {
        $draft = $this->ownedDraft($teacher, $draftId);
        if (! $draft->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['請先進入編輯再修改草稿'],
            ]);
        }

        return $draft;
    }

    /** 確認這份 Draft 屬於目前教師，且課程也是他的。 */
    private function ownedDraft(Teacher $teacher, int $draftId): MaterialDraft
    {
        $draft = MaterialDraft::query()
            ->whereKey($draftId)
            ->where('teacher_id', $teacher->id)
            ->first();

        if ($draft === null) {
            throw new ModelNotFoundException();
        }

        $this->courseService->findForTeacher($teacher, $draft->course_id);

        return $draft;
    }

    /**
     * 在 tree 裡找到指定主題（回傳引用，才能直接改）。
     *
     * @param  array{topics: list<array<string, mixed>>}  $tree
     * @return array<string, mixed>
     */
    private function &findTopic(array &$tree, string $nodeId): array
    {
        foreach ($tree['topics'] as &$topic) {
            if ($topic['id'] === $nodeId) {
                return $topic;
            }
        }

        throw new ModelNotFoundException();
    }

    /**
     * 在 tree 裡找到指定章節。
     *
     * @param  array{topics: list<array<string, mixed>>}  $tree
     * @return array<string, mixed>
     */
    private function &findChapter(array &$tree, string $nodeId): array
    {
        foreach ($tree['topics'] as &$topic) {
            foreach ($topic['chapters'] as &$chapter) {
                if ($chapter['id'] === $nodeId) {
                    return $chapter;
                }
            }
        }

        throw new ModelNotFoundException();
    }

    /**
     * 在 tree 裡找到指定單元。
     *
     * @param  array{topics: list<array<string, mixed>>}  $tree
     * @return array<string, mixed>
     */
    private function &findUnit(array &$tree, string $nodeId): array
    {
        foreach ($tree['topics'] as &$topic) {
            foreach ($topic['chapters'] as &$chapter) {
                foreach ($chapter['units'] as &$unit) {
                    if ($unit['id'] === $nodeId) {
                        return $unit;
                    }
                }
            }
        }

        throw new ModelNotFoundException();
    }

    /**
     * 在 tree 裡找到指定知識卡。
     *
     * @param  array{topics: list<array<string, mixed>>}  $tree
     * @return array<string, mixed>
     */
    private function &findCard(array &$tree, string $nodeId): array
    {
        foreach ($tree['topics'] as &$topic) {
            foreach ($topic['chapters'] as &$chapter) {
                foreach ($chapter['units'] as &$unit) {
                    foreach ($unit['knowledge_cards'] as &$card) {
                        if ($card['id'] === $nodeId) {
                            return $card;
                        }
                    }
                }
            }
        }

        throw new ModelNotFoundException();
    }

    /**
     * 新建一個主題／章節／單元節點（含新 id、空的下一層）。
     *
     * @return array<string, mixed>
     */
    private function namedNode(string $name, string $childKey, ?int $sortOrder, int $count): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => $name,
            'sort_order' => $sortOrder ?? $count + 1,
            $childKey => [],
        ];
    }

    /**
     * 取出所有 Topic 名稱（不是教材名稱）。
     *
     * @param  list<array<string, mixed>>  $topics
     * @return list<string>
     */
    private function topicNames(array $topics): array
    {
        return array_map(fn (array $topic) => trim((string) $topic['name']), $topics);
    }

    /**
     * 同一份 Draft 裡 Topic 名稱不可重複。
     *
     * @param  list<string>  $names
     */
    private function assertUniqueTopicNames(array $names, string $errorKey): void
    {
        $names = array_map(fn (string $name) => trim($name), $names);
        $counts = array_count_values($names);
        $duplicates = array_keys(array_filter($counts, fn (int $count) => $count > 1));
        if ($duplicates !== []) {
            throw ValidationException::withMessages([
                $errorKey => [$this->duplicateNameMessage($duplicates)],
            ]);
        }
    }

    /** Excel 匯入：該課若還有未發布草稿用同一個教材名稱就 422。 */
    private function assertMaterialNameAvailable(int $courseId, string $name): void
    {
        $exists = MaterialDraft::query()
            ->where('course_id', $courseId)
            ->where('name', $name)
            ->where('status', MaterialDraft::STATUS_DRAFT)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'file' => ['教材名稱「'.$name.'」已存在，請修改後再試'],
            ]);
        }
    }

    /**
     * 把 Draft 裡的一個主題同步到正式表：同名或同 id 則更新，否則新增。
     *
     * @param  array<string, mixed>  $topicNode
     */
    private function syncOfficialTopic(int $courseId, array $topicNode): void
    {
        $topic = $this->findOfficialNode(
            Topic::query()->where('course_id', $courseId),
            $topicNode,
            'name',
        );

        $payload = [
            'name' => trim((string) $topicNode['name']),
            'sort_order' => $topicNode['sort_order'] ?? 0,
        ];

        if ($topic === null) {
            $topic = Topic::query()->create([
                'course_id' => $courseId,
                ...$payload,
            ]);
        } else {
            $topic->update($payload);
        }

        $keptChapterIds = [];
        foreach ($topicNode['chapters'] ?? [] as $chapterNode) {
            $keptChapterIds[] = $this->syncOfficialChapter($topic, $chapterNode);
        }

        $topic->load('chapters');
        foreach ($topic->chapters as $chapter) {
            if (! in_array($chapter->id, $keptChapterIds, true)) {
                $this->tryDeleteChapter($chapter);
            }
        }

        $topic->touch();
    }

    /**
     * @param  array<string, mixed>  $chapterNode
     */
    private function syncOfficialChapter(Topic $topic, array $chapterNode): int
    {
        $chapter = $this->findOfficialNode(
            Chapter::query()->where('topic_id', $topic->id),
            $chapterNode,
            'name',
        );

        $payload = [
            'name' => trim((string) $chapterNode['name']),
            'sort_order' => $chapterNode['sort_order'] ?? 0,
        ];

        if ($chapter === null) {
            $chapter = Chapter::query()->create([
                'topic_id' => $topic->id,
                ...$payload,
            ]);
        } else {
            $chapter->update($payload);
        }

        $keptUnitIds = [];
        foreach ($chapterNode['units'] ?? [] as $unitNode) {
            $keptUnitIds[] = $this->syncOfficialUnit($chapter, $unitNode);
        }

        $chapter->load('units');
        foreach ($chapter->units as $unit) {
            if (! in_array($unit->id, $keptUnitIds, true)) {
                $this->tryDeleteUnit($unit);
            }
        }

        return (int) $chapter->id;
    }

    /**
     * @param  array<string, mixed>  $unitNode
     */
    private function syncOfficialUnit(Chapter $chapter, array $unitNode): int
    {
        $unit = $this->findOfficialNode(
            Unit::query()->where('chapter_id', $chapter->id),
            $unitNode,
            'name',
        );

        $payload = [
            'name' => trim((string) $unitNode['name']),
            'sort_order' => $unitNode['sort_order'] ?? 0,
        ];

        if ($unit === null) {
            $unit = Unit::query()->create([
                'chapter_id' => $chapter->id,
                ...$payload,
            ]);
        } else {
            $unit->update($payload);
        }

        $keptCardIds = [];
        foreach ($unitNode['knowledge_cards'] ?? [] as $cardNode) {
            $keptCardIds[] = $this->syncOfficialCard($unit, $cardNode);
        }

        $unit->load('knowledgeCards');
        foreach ($unit->knowledgeCards as $card) {
            if (! in_array($card->id, $keptCardIds, true)) {
                $this->tryDeleteCard($card);
            }
        }

        return (int) $unit->id;
    }

    /**
     * @param  array<string, mixed>  $cardNode
     */
    private function syncOfficialCard(Unit $unit, array $cardNode): int
    {
        $card = $this->findOfficialNode(
            KnowledgeCard::query()->where('unit_id', $unit->id),
            $cardNode,
            'title',
        );

        $payload = [
            'title' => (string) $cardNode['title'],
            'content' => (string) $cardNode['content'],
            'example' => $cardNode['example'] ?? null,
            'sort_order' => $cardNode['sort_order'] ?? 0,
        ];

        if ($card === null) {
            $card = KnowledgeCard::query()->create([
                'unit_id' => $unit->id,
                ...$payload,
            ]);
        } else {
            $card->update($payload);
        }

        return (int) $card->id;
    }

    /**
     * 先用正式表 id（從已發布複製的 Draft），否則用名稱／標題對。
     *
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $node
     */
    private function findOfficialNode(Builder $query, array $node, string $nameColumn): ?Model
    {
        $id = $node['id'] ?? null;
        if (is_int($id) || (is_string($id) && preg_match('/^[1-9][0-9]*$/', $id) === 1)) {
            $found = (clone $query)->whereKey((int) $id)->first();
            if ($found !== null) {
                return $found;
            }
        }

        $name = trim((string) ($node[$nameColumn] ?? ''));
        if ($name === '') {
            return null;
        }

        return (clone $query)->where($nameColumn, $name)->first();
    }

    private function tryDeleteChapter(Chapter $chapter): void
    {
        $chapter->load('units.knowledgeCards');
        foreach ($chapter->units as $unit) {
            $this->tryDeleteUnit($unit);
        }

        if ($chapter->units()->exists()) {
            return;
        }

        $chapter->delete();
    }

    private function tryDeleteUnit(Unit $unit): void
    {
        $unit->load('knowledgeCards');
        foreach ($unit->knowledgeCards as $card) {
            $this->tryDeleteCard($card);
        }

        if ($unit->knowledgeCards()->exists()) {
            return;
        }

        $unit->delete();
    }

    private function tryDeleteCard(KnowledgeCard $card): void
    {
        if ($card->questions()->exists()) {
            return;
        }

        $card->delete();
    }

    /**
     * 從正式表組成一份可編輯的 tree；節點 id 用正式表 id，發布時才能對到原列。
     *
     * @return list<array<string, mixed>>
     */
    private function treeFromOfficial(int $courseId, ?int $topicId = null): array
    {
        $query = Topic::query()
            ->where('course_id', $courseId)
            ->with(['chapters.units.knowledgeCards'])
            ->orderBy('sort_order');

        if ($topicId !== null) {
            $query->whereKey($topicId);
        }

        $topics = $query->get();

        $tree = [];
        foreach ($topics as $topic) {
            $chapters = [];
            foreach ($topic->chapters as $chapter) {
                $units = [];
                foreach ($chapter->units as $unit) {
                    $cards = [];
                    foreach ($unit->knowledgeCards as $card) {
                        $cards[] = [
                            'id' => (string) $card->id,
                            'title' => $card->title,
                            'content' => $card->content,
                            'example' => $card->example,
                            'sort_order' => $card->sort_order,
                        ];
                    }
                    $units[] = [
                        'id' => (string) $unit->id,
                        'name' => $unit->name,
                        'sort_order' => $unit->sort_order,
                        'knowledge_cards' => $cards,
                    ];
                }
                $chapters[] = [
                    'id' => (string) $chapter->id,
                    'name' => $chapter->name,
                    'sort_order' => $chapter->sort_order,
                    'units' => $units,
                ];
            }
            $tree[] = [
                'id' => (string) $topic->id,
                'name' => $topic->name,
                'sort_order' => $topic->sort_order,
                'chapters' => $chapters,
            ];
        }

        return $tree;
    }

    /**
     * Topic 重名時給老師看的訊息。
     *
     * @param  list<string>  $names
     */
    private function duplicateNameMessage(array $names): string
    {
        return '同一份教材內主題名稱重複（'.implode('、', $names).'），請修改後再試';
    }
}
