<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $active = $request->input('active', null);
        $available = $request->input('available', null); // Para listar apenas eventos disponíveis para inscrição

        $query = Event::with('paymentMethods');

        if ($active !== null) {
            $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        // Se available=true, mostrar apenas eventos que ainda aceitam inscrições (end_date >= hoje)
        if ($available === 'true' || $available === true || $available === 1) {
            $query->where('active', true)
                  ->where('end_date', '>=', now()->format('Y-m-d'));
        }

        // Se não especificou per_page e available=true, retornar todos (sem paginação)
        if ($available === 'true' || $available === true || $available === 1) {
            $events = $query->orderByDesc('start_date')->get();
            return response()->json($events, 200);
        }

        $events = $query->orderByDesc('start_date')
            ->paginate($perPage);

        return response()->json($events, 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'image' => 'nullable|string', // base64 ou URL
            'price' => 'required|integer|min:0',
            'active' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => 'in:PIX,BOLETO,CREDIT_CARD,FREE',
        ]);

        return DB::transaction(function () use ($data) {
            $imagePath = null;
            if (!empty($data['image'])) {
                $imagePath = $this->storeImage($data['image']);
            }

            $event = Event::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'image' => $imagePath,
                'price' => $data['price'],
                'active' => $data['active'] ?? true,
            ]);

            // Se o evento é gratuito, adiciona FREE automaticamente
            $paymentMethods = $data['payment_methods'] ?? [];
            if ($event->isFree() && !in_array('FREE', $paymentMethods)) {
                $paymentMethods[] = 'FREE';
            }

            // Se não há métodos de pagamento e não é gratuito, adiciona os padrões
            if (empty($paymentMethods) && !$event->isFree()) {
                $paymentMethods = ['PIX', 'BOLETO', 'CREDIT_CARD'];
            }

            foreach ($paymentMethods as $method) {
                EventPaymentMethod::create([
                    'event_id' => $event->id,
                    'method' => $method,
                    'active' => true,
                ]);
            }

            return response()->json($event->load('paymentMethods'), 201);
        });
    }

    public function show($id)
    {
        $event = Event::with('paymentMethods')->findOrFail($id);
        return response()->json($event, 200);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'image' => 'nullable|string',
            'price' => 'sometimes|integer|min:0',
            'active' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => 'in:PIX,BOLETO,CREDIT_CARD,FREE',
        ]);

        return DB::transaction(function () use ($event, $data) {
            if (!empty($data['image'])) {
                // Remove imagem antiga se existir
                if ($event->image && Storage::disk('public')->exists($event->image)) {
                    Storage::disk('public')->delete($event->image);
                }
                $data['image'] = $this->storeImage($data['image']);
            }

            $event->update($data);

            // Atualizar métodos de pagamento
            if (isset($data['payment_methods'])) {
                $event->paymentMethods()->delete();

                $paymentMethods = $data['payment_methods'];
                if ($event->isFree() && !in_array('FREE', $paymentMethods)) {
                    $paymentMethods[] = 'FREE';
                }

                foreach ($paymentMethods as $method) {
                    EventPaymentMethod::create([
                        'event_id' => $event->id,
                        'method' => $method,
                        'active' => true,
                    ]);
                }
            }

            return response()->json($event->load('paymentMethods'), 200);
        });
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        // Verificar se há inscrições
        if ($event->registrations()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover este evento. Ele já possui inscrições vinculadas.'
            ], 422);
        }

        return DB::transaction(function () use ($event) {
            // Remover imagem
            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }

            $event->paymentMethods()->delete();
            $event->delete();

            return response()->json([
                'message' => 'Evento removido com sucesso.'
            ], 200);
        });
    }

    private function storeImage(string $imageData): string
    {
        // Se já é uma URL, retornar
        if (preg_match('/^https?:\/\//', $imageData)) {
            $parsed = parse_url($imageData, PHP_URL_PATH);
            return ltrim(str_replace('/storage/', '', $parsed), '/');
        }

        // Se é base64
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = strtolower($matches[1]);
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;
            $allowedExtensions = ['jpg', 'png', 'gif', 'webp'];

            if (!in_array($extension, $allowedExtensions, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'image' => ['Formato de imagem não suportado.'],
                ]);
            }

            $data = substr($imageData, strpos($imageData, ',') + 1);
            $decoded = base64_decode($data, true);

            if ($decoded === false) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'image' => ['Não foi possível processar a imagem enviada.'],
                ]);
            }

            $path = "events/" . now()->format('Y/m/') . Str::random(40) . '.' . $extension;
            Storage::disk('public')->put($path, $decoded);

            return $path;
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'image' => ['Formato de imagem inválido.'],
        ]);
    }
}
