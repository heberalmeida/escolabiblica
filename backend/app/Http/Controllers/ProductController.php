<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductImage;
use App\Models\VariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\FirebaseService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $products = Product::with(['variants.images', 'images'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($products, 200);
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price'  => 'required|integer',
            'old_price'   => 'nullable|integer',
            'active'      => 'boolean',
            'infinite_stock' => 'boolean',
            'variants'    => 'array',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.fit'   => 'nullable|string|max:50',
            'variants.*.size'  => 'required|string|max:20',
            'variants.*.price_override' => 'nullable|integer',
            'variants.*.stock' => 'nullable|integer',
            'variants.*.size_description' => 'nullable|string',
            'variants.*.active' => 'boolean',
            'variants.*.featured' => 'boolean',
            'group_images' => 'array',
            'group_images.*.fit' => 'nullable|string|max:50',
            'group_images.*.images' => 'array',
            'group_images.*.images.*.url' => 'nullable|string',
            'group_images.*.images.*.dataUrl' => 'nullable|string',
            'group_images.*.images.*.position' => 'nullable|integer',
            'images'      => 'array',
            'images.*.url' => 'nullable|string',
            'images.*.dataUrl' => 'nullable|string',
            'images.*.position' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($data, $firebase) {
            $product = Product::create([
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'base_price'     => $data['base_price'],
                'old_price'      => $data['old_price'] ?? null,
                'active'         => $data['active'] ?? true,
                'infinite_stock' => $data['infinite_stock'] ?? true,
            ]);

            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $v) {
                    $variant = $product->variants()->create($v);

                    if (!empty($v['images'])) {
                        $this->syncVariantImages($variant, $v['images']);
                    }
                }
            }

            if (!empty($data['group_images'])) {
                foreach ($data['group_images'] as $group) {
                    if (empty($group['fit']) || empty($group['images'])) {
                        continue;
                    }

                    $existingVariants = $product->variants()->where('fit', $group['fit'])->get();
                    foreach ($existingVariants as $variant) {
                        $this->syncVariantImages($variant, $group['images']);
                    }
                }
            }

            $this->syncImages($product, $data['images'] ?? []);
            $this->touchProductsWebhook($firebase);

            return response()->json($product->load(['variants', 'images']), 201);
        });
    }

    public function show($id)
    {
        $product = Product::with(['variants.images', 'images'])->findOrFail($id);
        return response()->json($product, 200);
    }

    public function update(Request $request, $id, FirebaseService $firebase)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price'  => 'sometimes|integer',
            'old_price'   => 'nullable|integer',
            'active'      => 'boolean',
            'infinite_stock' => 'boolean',
            'variants'    => 'array',
            'variants.*.id' => 'nullable|integer|exists:variants,id',
            'variants.*.color' => 'required_with:variants|string|max:50',
            'variants.*.fit'   => 'nullable|string|max:50',
            'variants.*.size'  => 'required_with:variants|string|max:20',
            'variants.*.price_override' => 'nullable|integer',
            'variants.*.stock' => 'nullable|integer',
            'variants.*.size_description' => 'nullable|string',
            'variants.*.active' => 'boolean',
            'variants.*.featured' => 'boolean',
            'group_images' => 'array',
            'group_images.*.fit' => 'nullable|string|max:50',
            'group_images.*.images' => 'array',
            'group_images.*.images.*.url' => 'nullable|string',
            'group_images.*.images.*.dataUrl' => 'nullable|string',
            'group_images.*.images.*.position' => 'nullable|integer',
            'images'      => 'array',
            'images.*.id' => 'nullable|integer|exists:product_images,id',
            'images.*.url' => 'nullable|string',
            'images.*.dataUrl' => 'nullable|string',
            'images.*.position' => 'nullable|integer',
        ]);

        $images = $data['images'] ?? null;
        unset($data['images']);

        return DB::transaction(function () use ($product, $data, $images, $firebase) {
            $product->update($data);

            if (isset($data['variants'])) {
                foreach ($data['variants'] as $v) {
                    if (isset($v['id'])) {
                        $variant = Variant::find($v['id']);
                        if ($variant) {
                            $variant->update($v);
                            if (!empty($v['images'])) {
                                $this->syncVariantImages($variant, $v['images']);
                            }
                        }
                    } else {
                        $variant = $product->variants()->create($v);
                        if (!empty($v['images'])) {
                            $this->syncVariantImages($variant, $v['images']);
                        }
                    }
                }
            }

            if (!empty($data['group_images'])) {
                foreach ($data['group_images'] as $group) {
                    if (empty($group['fit']) || empty($group['images'])) {
                        continue;
                    }

                    $existingVariants = $product->variants()->where('fit', $group['fit'])->get();
                    foreach ($existingVariants as $variant) {
                        $this->syncVariantImages($variant, $group['images']);
                    }
                }
            }

            if ($images !== null) {
                $this->syncImages($product, $images);
            }

            $this->touchProductsWebhook($firebase);

            return response()->json($product->load(['variants', 'images']), 200);
        });
    }

    public function destroy($id, FirebaseService $firebase)
    {
        $product = Product::with(['variants', 'images'])->findOrFail($id);

        $hasOrders = \App\Models\OrderItem::where('product_id', $product->id)
            ->orWhereIn('variant_id', $product->variants->pluck('id'))
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'message' => 'Não é possível remover este produto. Ele já possui vendas vinculadas.'
            ], 422);
        }

        return DB::transaction(function () use ($product, $firebase) {
            foreach ($product->images as $image) {
                $path = $image->url;

                if (str_contains($path, '/storage/')) {
                    $path = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));
                }
                $path = ltrim($path, '/');

                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $image->delete();
            }

            foreach ($product->variants as $variant) {
                $variant->delete();
            }

            $product->delete();
            $this->touchProductsWebhook($firebase);

            return response()->json([
                'message' => 'Produto removido com sucesso.'
            ], 200);
        });
    }

    public function destroyImage($id, FirebaseService $firebase)
    {
        $image = ProductImage::findOrFail($id);

        $path = $image->url;

        if (str_contains($path, '/storage/')) {
            $path = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));
        }

        $path = ltrim($path, '/');

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $image->delete();
        $this->touchProductsWebhook($firebase);

        return response()->json(['message' => 'Imagem removida com sucesso'], 200);
    }

    public function destroyVariant($id, FirebaseService $firebase)
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();
        $this->touchProductsWebhook($firebase);

        return response()->json(['message' => 'Variação removida com sucesso'], 200);
    }

    private function syncImages(Product $product, array $images): void
    {
        $ids = collect($images)->pluck('id')->filter()->all();
        if (!empty($ids)) {
            $product->images()->whereNotIn('id', $ids)->delete();
        }

        foreach ($images as $index => $image) {
            if (!empty($image['id'])) {
                $payload = ['position' => $image['position'] ?? $index];
                if (!empty($image['dataUrl'])) {
                    $payload['url'] = $this->storeBase64Image($image['dataUrl']);
                } elseif (!empty($image['url']) && preg_match('/^https?:\/\//', $image['url'])) {
                    $parsed = parse_url($image['url'], PHP_URL_PATH);
                    $payload['url'] = ltrim(str_replace('/storage/', '', $parsed), '/');
                }
                $product->images()->where('id', $image['id'])->update($payload);
            } else {
                $payload = $this->prepareImageAttributes($image, $index);
                if (!empty($payload['url'])) {
                    $product->images()->create($payload);
                }
            }
        }
    }

    private function syncVariantImages(Variant $variant, array $images): void
    {
        $ids = collect($images)->pluck('id')->filter()->all();

        if (!empty($ids)) {
            $variant->images()->whereNotIn('id', $ids)->delete();
        }

        foreach ($images as $index => $image) {
            if (!empty($image['id'])) {
                if (!empty($image['dataUrl'])) {
                    $payload = [
                        'url' => $this->storeBase64Image($image['dataUrl']),
                        'position' => $image['position'] ?? $index,
                    ];
                    $variant->images()->where('id', $image['id'])->update($payload);
                } else {
                    $variant->images()->where('id', $image['id'])->update([
                        'position' => $image['position'] ?? $index,
                    ]);
                }

                continue;
            }

            if (!empty($image['dataUrl'])) {
                $payload = [
                    'url' => $this->storeBase64Image($image['dataUrl']),
                    'position' => $image['position'] ?? $index,
                ];
                $variant->images()->create($payload);
            }
        }
    }

    private function prepareImageAttributes(array $image, int $fallbackPosition = 0): array
    {
        $position = $image['position'] ?? $fallbackPosition;
        $url = $image['url'] ?? null;

        if (!empty($image['dataUrl'])) {
            $url = $this->storeBase64Image($image['dataUrl']);
        }

        if (!$url) {
            return [];
        }

        return [
            'url' => $url,
            'position' => $position,
        ];
    }

    private function storeBase64Image(string $dataUrl): string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'images' => ['Formato de imagem inválido.'],
            ]);
        }

        $extension = strtolower($matches[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $allowedExtensions = ['jpg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'images' => ['Formato de imagem não suportado.'],
            ]);
        }

        $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'images' => ['Não foi possível processar a imagem enviada.'],
            ]);
        }

        $isVariantContext = str_contains(request()->path(), 'variant')
            || str_contains(request()->path(), 'group')
            || str_contains(request()->path(), 'products');

        $folder = $isVariantContext ? 'variant_images' : 'products';
        $path = "{$folder}/" . now()->format('Y/m/') . Str::random(40) . '.' . $extension;

        Storage::disk('public')->put($path, $decoded);

        return $path;
    }

    private function touchProductsWebhook(FirebaseService $firebase): void
    {
        $empresaId = (string) (config('app.empresa_id') ?? 'default');
        try {
            $firebase->updateLastModified('products', $empresaId);
        } catch (\Throwable $e) {
        }
    }
}
