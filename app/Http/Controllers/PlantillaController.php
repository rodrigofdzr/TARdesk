<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    public function index()
    {
        return view('plantillas.index'); // la vista que tú ya hiciste
    }
}
