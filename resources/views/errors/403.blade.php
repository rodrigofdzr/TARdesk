<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - No tienes permisos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-md">
        <h1 class="text-4xl font-bold text-red-600 mb-4">403</h1>
        <p class="text-lg text-gray-700 mb-6">No tienes permisos para acceder a esta sección.</p>
        <form method="POST" action="/admin/logout">
            @csrf
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded">
                Cerrar sesión
            </button>
        </form>
        <p class="mt-4 text-sm text-gray-500">Inicia sesión con un usuario autorizado para acceder.</p>
    </div>
</body>
</html>

