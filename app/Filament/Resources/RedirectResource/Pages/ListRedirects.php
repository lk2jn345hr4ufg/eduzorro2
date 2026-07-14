<?php

namespace App\Filament\Resources\RedirectResource\Pages;

use App\Filament\Resources\RedirectResource;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->bulkCreateAction(),
            $this->importCsvAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Modal: paste many "FROM -> TO" lines at once, one shared match
     * type / status code / active state applied to every line.
     */
    protected function bulkCreateAction(): Action
    {
        return Action::make('bulkCreate')
            ->label('Bulk add')
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->modalHeading('Bulk add redirects')
            ->modalWidth('2xl')
            ->form([
                Textarea::make('pairs')
                    ->label('Redirects')
                    ->required()
                    ->rows(10)
                    ->placeholder("/old-page -> /new-page\n/old-region/en -> /global/en\n/retired-category, /global/en/new-category")
                    ->helperText('One per line: FROM -> TO (arrow, or a comma also works). The settings below apply to every line.'),

                Select::make('match_type')
                    ->options(['exact' => 'Exact match', 'prefix' => 'Prefix'])
                    ->default('exact')
                    ->required(),

                Select::make('status_code')
                    ->options([301 => '301 — Permanent', 302 => '302 — Temporary'])
                    ->default(301)
                    ->required(),

                Toggle::make('is_active')->default(true),
            ])
            ->action(function (array $data) {
                $lines            = preg_split('/\r\n|\r|\n/', trim($data['pairs']));
                [$created, $updated, $skipped] = [0, 0, []];

                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }

                    if (preg_match('/^(.*?)\s*(?:->|=>)\s*(.+)$/', $line, $m)) {
                        [$from, $to] = [trim($m[1]), trim($m[2])];
                    } elseif (str_contains($line, ',')) {
                        [$from, $to] = array_map('trim', explode(',', $line, 2));
                    } else {
                        $skipped[] = 'Line ' . ($i + 1) . ": couldn't parse \"{$line}\"";
                        continue;
                    }

                    if ($from === '' || $to === '') {
                        $skipped[] = 'Line ' . ($i + 1) . ': missing from or to';
                        continue;
                    }

                    $normalizedFrom = Redirect::normalizePath($from);
                    $normalizedTo   = Redirect::normalizeTarget($to);

                    if ($normalizedFrom === $normalizedTo) {
                        $skipped[] = 'Line ' . ($i + 1) . ": from and to are the same (\"{$normalizedFrom}\")";
                        continue;
                    }

                    $record = Redirect::updateOrCreate(
                        ['from_path' => $normalizedFrom],
                        [
                            'to_path'     => $normalizedTo,
                            'match_type'  => $data['match_type'],
                            'status_code' => $data['status_code'],
                            'is_active'   => $data['is_active'],
                        ]
                    );

                    $record->wasRecentlyCreated ? $created++ : $updated++;
                }

                $this->notifyImportResult($created, $updated, $skipped);
            });
    }

    /**
     * Modal: upload a CSV with a header row. Recognised columns:
     * from_path, to_path (required), status_code, match_type, is_active, notes.
     */
    protected function importCsvAction(): Action
    {
        return Action::make('importCsv')
            ->label('Import CSV')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Import redirects from CSV')
            ->modalDescription('First row must be a header. Required columns: from_path, to_path. Optional: status_code, match_type, is_active, notes.')
            ->form([
                FileUpload::make('csv')
                    ->label('CSV file')
                    ->required()
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('redirect-imports')
                    ->visibility('private'),
            ])
            ->action(function (array $data) {
                $path    = $data['csv'];
                $content = Storage::disk('local')->get($path);
                Storage::disk('local')->delete($path);

                if (! $content || trim($content) === '') {
                    Notification::make()->title('That CSV was empty')->warning()->send();
                    return;
                }

                [$created, $updated, $skipped] = $this->parseCsv($content);

                $this->notifyImportResult($created, $updated, $skipped);
            });
    }

    protected function parseCsv(string $content): array
    {
        $lines  = preg_split('/\r\n|\r|\n/', trim($content));
        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(array_shift($lines)));

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            $row    = array_combine($header, array_pad($values, count($header), null));

            $from = $row['from_path'] ?? $row['from'] ?? null;
            $to   = $row['to_path'] ?? $row['to'] ?? null;

            if (! $from || ! $to) {
                $skipped[] = 'Row ' . ($i + 2) . ': missing from_path or to_path';
                continue;
            }

            $normalizedFrom = Redirect::normalizePath($from);
            $normalizedTo   = Redirect::normalizeTarget($to);

            if ($normalizedFrom === $normalizedTo) {
                $skipped[] = 'Row ' . ($i + 2) . ': from_path and to_path are the same';
                continue;
            }

            $statusCode = (int) ($row['status_code'] ?? 301);
            if (! in_array($statusCode, [301, 302, 307, 308], true)) {
                $statusCode = 301;
            }

            $matchType = strtolower(trim((string) ($row['match_type'] ?? 'exact')));
            if (! in_array($matchType, ['exact', 'prefix'], true)) {
                $matchType = 'exact';
            }

            $isActiveRaw = strtolower(trim((string) ($row['is_active'] ?? '1')));
            $isActive    = ! in_array($isActiveRaw, ['0', 'false', 'no', ''], true);

            $record = Redirect::updateOrCreate(
                ['from_path' => $normalizedFrom],
                [
                    'to_path'     => $normalizedTo,
                    'status_code' => $statusCode,
                    'match_type'  => $matchType,
                    'is_active'   => $isActive,
                    'notes'       => $row['notes'] ?? null,
                ]
            );

            $record->wasRecentlyCreated ? $created++ : $updated++;
        }

        return [$created, $updated, $skipped];
    }

    protected function notifyImportResult(int $created, int $updated, array $skipped): void
    {
        $body = "{$created} created, {$updated} updated.";

        if ($skipped) {
            $body .= ' ' . count($skipped) . ' skipped — first issue: ' . $skipped[0];
        }

        Notification::make()
            ->title('Redirects processed')
            ->body($body)
            ->color($skipped ? 'warning' : 'success')
            ->send();
    }
}
