<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SectorScope
{
    /**
     * Restringe gestores de setor a visualizar apenas dados do próprio setor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('gestor_setor')) {
            // Se for rota de pedidos, injeta filtro automático
            $request->merge([
                'sector_scope' => $user->sector_id,
            ]);
        }

        return $next($request);
    }
}
