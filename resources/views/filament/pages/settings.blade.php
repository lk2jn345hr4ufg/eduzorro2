<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Note</x-slot>
        <x-slot name="description">
            Keys are stored in the database and override the values in
            <code>.env</code>. Leave a field empty to fall back to the
            <code>.env</code>/config value. Anyone with admin access can read
            these, and they may appear in database backups — fine for local use,
            but prefer <code>.env</code> for production secrets.
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>
