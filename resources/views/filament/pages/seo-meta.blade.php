<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Where each page is edited</x-slot>
        <x-slot name="description">
            This screen covers pages that have no record of their own. Everything
            else is edited in the SEO section at the bottom of its own form:
            regions, industries, categories, sports, countries, teams, study
            tools and team news. Empty fields always mean “keep the generated
            tag”, so partial edits are safe.
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>
