<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => '',
        'version' => '1.0.0',
        'status' => 'OK'
    ]);
});

Route::get('/storage/{path}', function ($path) {
    $path = urldecode($path);

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($path);
    $mime = Storage::disk('public')->mimeType($path);

    return Response::make($file, 200)->header('Content-Type', $mime);
})->where('path', '.*');
