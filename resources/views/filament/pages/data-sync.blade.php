<x-filament-panels::page>
    <div class="fi-ta-ctn grid gap-4">
        <x-filament::section>
            <x-slot name="heading">API-Football — teams & countries</x-slot>
            <x-slot name="description">
                Imports the football taxonomy (countries and teams) from the leagues
                configured in <code>config/football.php</code> for the selected season.
                Run this after changing the leagues list or season. Use the
                “Import teams &amp; countries” button above.
            </x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Fixtures, standings & transfers</x-slot>
            <x-slot name="description">
                These are stored in your database, so team pages render from local data
                instead of calling the API on every view. Re-run the sync to refresh —
                fixtures after each matchday, standings weekly, transfers during the
                windows. Each team costs 1&ndash;2 API calls, so use the per-run limit
                and the country filter to stay inside your quota. If a team has never
                been synced, its tab falls back to a live API read.
            </x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Team news</x-slot>
            <x-slot name="description">
                Pulls recent articles per team from the news API into the Team news table.
                News API free tiers are rate-limited, so keep the per-run limit modest and
                lean on the scheduled hourly sync for full coverage. You can also fetch news
                for a single team from its row on the Teams page.
            </x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Live data cache</x-slot>
            <x-slot name="description">
                Fixtures, standings and transfers are fetched live and cached
                (15&nbsp;min / 1&nbsp;h / 24&nbsp;h). Clear the cache to force a refresh
                on the next page view.
            </x-slot>
        </x-filament::section>
    </div>
</x-filament-panels::page>
