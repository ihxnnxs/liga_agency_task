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

    public function __construct()
    {
    }

    /**
     *
     * @throws Exception
     */
    public function handle(
        GoogleSheetService $sheetService,
        CacheRepository $cache,
        LoggerInterface $log
    ): void {
        $sheetId = $cache->get(self::CACHE_KEY_SHEET_ID);

        if (!$sheetId) {
            $log->warning('Задача синхронизации остановлена: ID таблицы не настроен.');
            return;
        }

        $log->info("Начинаем синхронизацию с таблицей ID: {$sheetId}");

        try {
            $commentsMap = $this->getCommentsMap($sheetId, $log, $sheetService);
            $itemsToSync = $this->getItemsToSync($log);
            $finalData = $this->prepareFinalData($itemsToSync, $commentsMap);

            $sheetService->clearSheet($sheetId, self::SHEET_NAME);
            $sheetService->updateSheet($sheetId, self::SHEET_NAME, $finalData);

            $log->info("Синхронизация с таблицей ID: {$sheetId} успешно завершена.");
        } catch (Exception $e) {
            $log->error("Ошибка при синхронизации с Google Sheets: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function getCommentsMap(string $sheetId, LoggerInterface $log, GoogleSheetService $sheetService): array
    {
        $sheetData = $sheetService->getSheetData($sheetId, self::SHEET_NAME);
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

        $log->info("Найдено " . count($commentsMap) . " комментариев в таблице.");
        return $commentsMap;
    }

    private function getItemsToSync(LoggerInterface $log): Collection
    {
        $items = Item::allowed()->orderBy('id')->get();
        $log->info("Найдено {$items->count()} записей со статусом 'Allowed' для выгрузки.");
        return $items;
    }

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
