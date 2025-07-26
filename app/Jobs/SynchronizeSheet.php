<?php

namespace App\Jobs;

use App\Models\Item;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

class SynchronizeSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SHEET_NAME = 'Лист1';
    private const CACHE_KEY_SHEET_ID = 'google_sheet_id';

    /**
     * Внедряем зависимости через конструктор.
     * Laravel автоматически предоставит экземпляры этих классов.
     */
    public function __construct(
        private readonly GoogleSheetService $sheetService,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $log
    ) {}

    /**
     * Основной метод, оркестрирующий процесс синхронизации.
     *
     * @throws Exception|\Psr\SimpleCache\InvalidArgumentException - перебрасывается для механизма повторных попыток Laravel
     */
    public function handle(): void
    {
        $sheetId = $this->cache->get(self::CACHE_KEY_SHEET_ID);

        if (!$sheetId) {
            $this->log->warning('Задача синхронизации остановлена: ID таблицы не настроен.');
            return;
        }

        $this->log->info("Начинаем синхронизацию с таблицей ID: {$sheetId}");

        try {
            $commentsMap = $this->getCommentsMap($sheetId);
            $itemsToSync = $this->getItemsToSync();
            $finalData = $this->prepareFinalData($itemsToSync, $commentsMap);

            $this->sheetService->clearSheet($sheetId, self::SHEET_NAME);
            $this->sheetService->updateSheet($sheetId, self::SHEET_NAME, $finalData);

            $this->log->info("Синхронизация с таблицей ID: {$sheetId} успешно завершена.");
        } catch (Exception $e) {
            $this->log->error("Ошибка при синхронизации с Google Sheets: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Перебрасываем исключение, чтобы Laravel понял, что задача провалилась
            throw $e;
        }
    }

    /**
     * Извлекает существующие комментарии из таблицы.
     * @throws Exception
     */
    private function getCommentsMap(string $sheetId): array
    {
        $sheetData = $this->sheetService->getSheetData($sheetId, self::SHEET_NAME);
        $commentsMap = [];

        if ($sheetData->isEmpty() || $sheetData->count() <= 1) {
            return $commentsMap;
        }

        $headers = collect($sheetData->shift())->map(fn ($h) => strtolower(trim($h ?? '')));
        $idIndex = $headers->search('id');
        $commentIndex = $headers->search('comment');

        if ($idIndex !== false && $commentIndex !== false) {
            foreach ($sheetData as $row) {
                if (!empty($row[$idIndex])) {
                    $commentsMap[$row[$idIndex]] = $row[$commentIndex] ?? '';
                }
            }
        }

        $this->log->info("Найдено " . count($commentsMap) . " комментариев в таблице.");
        return $commentsMap;
    }

    /**
     * Получает из БД записи, которые должны быть синхронизированы.
     */
    private function getItemsToSync(): Collection
    {
        $items = Item::allowed()->orderBy('id')->get();
        $this->log->info("Найдено {$items->count()} записей со статусом 'Allowed' для выгрузки.");
        return $items;
    }

    /**
     * Формирует итоговый массив данных для записи в таблицу.
     */
    private function prepareFinalData(Collection $items, array $commentsMap): array
    {
        $finalHeaders = ['id', 'name', 'status', 'created_at', 'updated_at', 'comment'];
        $finalData = [$finalHeaders];

        foreach ($items as $item) {
            $finalData[] = [
                $item->id,
                $item->name,
                $item->status->value,
                $item->created_at->toDateTimeString(),
                $item->updated_at->toDateTimeString(),
                $commentsMap[$item->id] ?? '',
            ];
        }

        return $finalData;
    }
}
