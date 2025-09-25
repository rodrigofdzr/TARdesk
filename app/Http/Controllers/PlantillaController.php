<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    // Mostrar todas las plantillas
    public function index()
    {
        // Aquí puedes traer las plantillas desde tu modelo, ejemplo: Plantilla::all()
        // Por ahora mostramos una vista simple
        return view('plantillas.index');
    }

    // Mostrar formulario para crear nueva plantilla
    public function create()
    {
        return view('plantillas.create');
    }

    // Guardar la nueva plantilla
    public function store(Request $request)
    {
        // Validación básica
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        // Guardar plantilla (aquí usarías tu modelo Plantilla)
        // Ejemplo: Plantilla::create($request->only(['nombre', 'contenido']));

        // Redirigir a la lista de plantillas con mensaje
        return redirect()->route('templates.index')->with('success', 'Plantilla creada correctamente');
    }
}
