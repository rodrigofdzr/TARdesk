<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Plantillas') }}
        </h2>
    </x-slot>

    <div class="p-6">
        <a href="{{ route('plantillas.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">Nueva Plantilla</a>
        <p class="mt-4 text-gray-700 dark:text-gray-300">Aquí iría la lista de plantillas.</p>
    </div>
</x-app-layout>
