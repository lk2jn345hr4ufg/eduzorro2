<?php

namespace App\Filament\Resources\TeamNewsResource\Pages;

use App\Filament\Resources\TeamNewsResource;
use App\Models\Language;
use App\Services\AI\GeminiClient;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTeamNews extends EditRecord
{
    protected static string $resource = TeamNewsResource::class;

    /** Holds the last Gemini result for preview → apply. */
    public ?array $geminiPreview = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('geminiRewrite')
                ->label('Rewrite with Gemini')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->modalHeading('Gemini rewrite & translation')
                ->modalSubmitActionLabel('Apply to form')
                ->modalWidth('4xl')
                // Generate when the modal opens, so the preview reflects the
                // current (possibly unsaved) form text.
                ->mountUsing(fn () => $this->geminiPreview = $this->generatePreview())
                ->modalContent(fn () => view('filament.news.gemini-preview', [
                    'preview' => $this->geminiPreview,
                    'locales' => $this->locales(),
                ]))
                ->action(function () {
                    if (! $this->geminiPreview) {
                        Notification::make()->title('Nothing to apply')->warning()->send();
                        return;
                    }

                    // Fill the form (not saved yet) so the editor can tweak, then Save.
                    $this->data['title']   = $this->geminiPreview['title'];
                    $this->data['excerpt'] = $this->geminiPreview['excerpt'];
                    $this->data['body']    = $this->geminiPreview['body'];

                    Notification::make()
                        ->title('Applied to the form')
                        ->body('Review the text and press Save to store it.')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    protected function locales(): array
    {
        return Language::query()->active()->ordered()->pluck('code')->all() ?: ['en'];
    }

    protected function generatePreview(): ?array
    {
        @set_time_limit(0);
        $gemini = app(GeminiClient::class);

        if (! $gemini->isConfigured()) {
            Notification::make()->title('Gemini key not set')
                ->body('Add it in Settings → Gemini.')->danger()->send();
            return null;
        }

        $article = [
            'title'   => $this->firstValue($this->data['title'] ?? null),
            'excerpt' => $this->firstValue($this->data['excerpt'] ?? null),
            'body'    => $this->firstValue($this->data['body'] ?? null),
        ];

        if (! $article['title']) {
            Notification::make()->title('Add a title first')->warning()->send();
            return null;
        }

        $rw = $gemini->rewriteArticle($article, $this->locales(), config('gemini.prompt'));

        if (! $rw) {
            Notification::make()->title('Gemini failed')->danger()->send();
        }

        return $rw ?: null;
    }

    protected function firstValue($json): ?string
    {
        if (is_array($json)) {
            foreach ($json as $v) {
                if ($v !== null && $v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }
}
