<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    /**
     * Mostrar listado de plantillas
     */
    public function index()
    {
        return view('plantillas.index');
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('plantillas.create');
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(Request $request)
    {
        // Validación rápida
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        // Aquí luego guardarías en BD
        // Plantilla::create($request->all());

        return redirect()->route('plantillas.index')
                         ->with('success', 'Plantilla creada correctamente.');
    }

    /**
     * Mostrar plantilla individual
     */
    public function show($id)
    {
        return view('plantillas.show', compact('id'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        return view('plantillas.edit', compact('id'));
    }

    /**
     * Actualizar plantilla
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        // Aquí luego actualizarías en BD
        // Plantilla::findOrFail($id)->update($request->all());

        return redirect()->route('plantillas.index')
                         ->with('success', 'Plantilla actualizada correctamente.');
    }

    /**
     * Eliminar plantilla
     */
    public function destroy($id)
    {
        // Plantilla::destroy($id);

        return redirect()->route('plantillas.index')
                         ->with('success', 'Plantilla eliminada correctamente.');
    }
}
