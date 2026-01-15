<?php

namespace App\Http\Controllers;

use App\Models\VariantImage;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseService;

class VariantImageController extends Controller
{
    public function destroy($id, FirebaseService $firebase)
    {
        $image = VariantImage::findOrFail($id);

        $path = $image->url;

        if (str_contains($path, '/storage/')) {
            $path = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));
        }

        $path = ltrim($path, '/');

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $image->delete();

        try {
            $firebase->updateLastModified('products', config('app.empresa_id', 'default'));
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'Imagem da variação removida com sucesso'], 200);
    }
}
