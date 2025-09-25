@extends('layouts.app')

@section('content')
<h1>Crear Nueva Plantilla</h1>

<form action="{{ route('templates.store') }}" method="POST">
    @csrf
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" id="nombre" required>
    
    <label for="contenido">Contenido:</label>
    <textarea name="contenido" id="contenido" required></textarea>

    <button type="submit">Guardar Plantilla</button>
</form>
@endsection
