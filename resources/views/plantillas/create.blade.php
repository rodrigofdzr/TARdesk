<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nueva Plantilla') }}
        </h2>
    </x-slot>

    <div class="p-6">
        <form action="{{ route('plantillas.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300">Título</label>
                <input type="text" name="titulo" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300">Contenido</label>
                <textarea name="contenido" class="w-full p-2 border rounded" rows="5" required></textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Guardar</button>
        </form>
    </div>
</x-app-layout>
