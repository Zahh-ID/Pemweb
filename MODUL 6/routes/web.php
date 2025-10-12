<?php

use Illuminate\Support\Facades\Route;

Route::get('/mahasiswa', function () {
    $arrMahasiswa =["Risa Lestari", "Rudi Hermawan", "Bambang Kusumo","Lisa Permata"];

    return view('univeristas.mahasiswa')->with('mahasiswa', $arrMahasiswa);
});
