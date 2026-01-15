<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $type  = $request->input('type');
        $color = $request->input('color');

        $products = Product::query()
            ->select('products.*')
            ->leftJoin('variants', 'variants.product_id', '=', 'products.id')
            ->where('products.active', true)
            ->when($type, fn($q) => $q->where('variants.fit', $type))
            ->when($color, fn($q) => $q->where('variants.color', $color))
            ->groupBy([
                'products.id',
                'products.name',
                'products.description',
                'products.base_price',
                'products.old_price',
                'products.active',
                'products.infinite_stock',
                'products.created_at',
                'products.updated_at'
            ])
            ->orderByDesc(DB::raw('MAX(variants.featured)'))
            ->orderByDesc('products.created_at')
            ->with(['variants', 'images'])
            ->get();

        $groups = collect();

        foreach ($products as $p) {
            $activeVariants = $p->variants->where('active', true);

            if ($type) {
                $activeVariants = $activeVariants->where('fit', $type);
            }
            if ($color) {
                $activeVariants = $activeVariants->where('color', $color);
            }

            if ($activeVariants->isEmpty()) {
                continue;
            }

            $fitGroups = $activeVariants->groupBy(fn($v) => $v->fit ?? 'Único');

            foreach ($fitGroups as $fit => $vars) {
                $colors = $vars->pluck('color')->filter()->unique()->values();
                $sizesByColor = [];

                foreach ($colors as $c) {
                    $sizesByColor[$c] = $vars->where('color', $c)
                        ->pluck('size')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }

                $firstVariant = $vars->sortBy('id')->first();

                $variantImages = $firstVariant && $firstVariant->images->isNotEmpty()
                    ? $firstVariant->images->sortBy('position')->values()
                    : $p->images->sortBy('position')->values();

                $groups->push([
                    'key' => "{$p->id}::{$fit}",
                    'product' => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'description' => $p->description,
                        'base_price' => $p->base_price,
                        'old_price' => $p->old_price,
                        'images' => $variantImages->map(fn($img) => [
                            'id' => $img->id,
                            'url' => $img->url,
                            'position' => $img->position,
                        ]),
                    ],
                    'type' => $fit,
                    'variants' => $vars
                        ->sortBy('id')
                        ->map(fn($v) => [
                            'id' => $v->id,
                            'name' => $v->fit ?? 'Único',
                            'color' => $v->color ?? 'Único',
                            'size' => $v->size ?? 'Único',
                            'stock' => $v->stock ?? 0,
                            'price' => $v->price_override ?? $p->base_price,
                            'active' => (bool) $v->active,
                            'featured' => (bool) $v->featured,
                        ])
                        ->values(),
                    'colors' => $colors,
                    'sizesByColor' => $sizesByColor,
                ]);
            }
        }

        $featured = $groups
            ->filter(fn($g) => collect($g['variants'])->contains(fn($v) => $v['featured']))
            ->shuffle();

        $nonFeatured = $groups
            ->reject(fn($g) => collect($g['variants'])->contains(fn($v) => $v['featured']))
            ->shuffle();

        $groups = $featured->concat($nonFeatured)->values();

        return response()->json(['data' => $groups], 200);
    }
}
