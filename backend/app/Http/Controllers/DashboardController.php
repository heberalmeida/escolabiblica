<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $sectorFilter = $request->query('sector');

        // Usar registrations em vez de orders
        $registrationsQuery = Registration::with('event')
            ->whereBetween('created_at', [$start, $end]);

        // Filtrar por setor se fornecido
        if ($sectorFilter) {
            $registrationsQuery->where('sector', $sectorFilter);
        }

        // ========== RELATÓRIO POR INSCRIÇÃO (INDIVIDUAL) ==========
        // Contar total de registrations individuais (não agrupadas)
        $totalRegistrations = (clone $registrationsQuery)->count();
        
        // Para contar inscrições pagas: se o pedido (payment_id) foi pago, todas as inscrições desse pedido são consideradas pagas
        // Primeiro, identificar todos os payment_ids pagos (verificar status do pedido como um todo)
        $paidPaymentIds = [];
        $allPaymentIdsForCheck = (clone $registrationsQuery)
            ->whereNotNull('asaas_payment_id')
            ->distinct()
            ->pluck('asaas_payment_id')
            ->toArray();

        foreach ($allPaymentIdsForCheck as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->first();
            
            if (!$firstReg) continue;
            
            // Aplicar filtro de setor se fornecido
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) {
                continue;
            }

            $paymentStatus = $this->resolvePaymentStatus($firstReg);
            if ($paymentStatus === 'paid') {
                $paidPaymentIds[] = $paymentId;
            }
        }

        // Contar todas as registrations que:
        // 1. Têm payment_id que está na lista de pedidos pagos (todas as inscrições do pedido são pagas), OU
        // 2. São gratuitas (sem payment_id) e têm status paid
        $paidRegistrationsQuery = clone $registrationsQuery;
        if (!empty($paidPaymentIds)) {
            $paidRegistrationsQuery->where(function($q) use ($paidPaymentIds) {
                // Se tem payment_id e está na lista de pedidos pagos
                $q->whereIn('asaas_payment_id', $paidPaymentIds)
                  // OU é gratuita e está paga
                  ->orWhere(function($q2) {
                      $q2->whereNull('asaas_payment_id')
                        ->where('payment_status', 'paid');
                  });
            });
        } else {
            // Se não há pedidos pagos, contar apenas gratuitas pagas
            $paidRegistrationsQuery->whereNull('asaas_payment_id')
                ->where('payment_status', 'paid');
        }
        $paidRegistrations = $paidRegistrationsQuery->count();

        // ========== RELATÓRIO POR PEDIDO (AGRUPADO) ==========
        // Buscar todos os payment_ids únicos no período (pedidos pagos)
        $allPaymentIds = (clone $registrationsQuery)
            ->whereNotNull('asaas_payment_id')
            ->distinct()
            ->pluck('asaas_payment_id')
            ->toArray();

        // Buscar registrations gratuitas (sem payment_id) - cada uma conta como um pedido separado
        $freeRegistrations = (clone $registrationsQuery)
            ->whereNull('asaas_payment_id')
            ->get();

        // Para cada payment_id, verificar o status do pedido (usar primeira registration do grupo)
        $totalOrders = 0;
        $paidOrders = 0;
        $pendingOrders = 0;
        $canceledOrders = 0;
        $overdueOrders = 0;
        $validatedOrders = 0;

        // Processar pedidos com payment_id
        foreach ($allPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->first();
            
            if (!$firstReg) continue;

            // Aplicar filtro de setor se fornecido
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) {
                continue;
            }

            $totalOrders++;

            $paymentStatus = $this->resolvePaymentStatus($firstReg);

            if ($paymentStatus === 'paid') {
                $paidOrders++;
            } elseif ($paymentStatus === 'pending') {
                $pendingOrders++;
            } elseif ($paymentStatus === 'canceled') {
                $canceledOrders++;
            } elseif ($paymentStatus === 'overdue') {
                $overdueOrders++;
            }

        }

        // Processar registrations gratuitas (cada uma é um "pedido" separado)
        foreach ($freeRegistrations as $freeReg) {
            // Aplicar filtro de setor se fornecido
            if ($sectorFilter && $freeReg->sector !== $sectorFilter) {
                continue;
            }

            $totalOrders++;
            
            // Gratuitas são consideradas pagas
            $paidOrders++;
        }

        // Contar registrations validadas
        $validatedRegistrations = (clone $registrationsQuery)
            ->where('validated', true)
            ->count();


        // Contar registrations não pagas: excluir as que pertencem a pedidos pagos
        $pendingRegistrations = (clone $registrationsQuery)
            ->where(function($q) use ($paidPaymentIds) {
                // Não está em um pedido pago
                if (!empty($paidPaymentIds)) {
                    $q->whereNotIn('asaas_payment_id', $paidPaymentIds)
                      ->orWhereNull('asaas_payment_id');
                }
            })
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->where('payment_status', 'pending')
                       ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') = 'PENDING'");
                })
                ->where('payment_status', '!=', 'paid')
                ->where('payment_status', '!=', 'canceled')
                ->where('payment_status', '!=', 'overdue')
                ->whereRaw("JSON_EXTRACT(gateway_payload, '$.status') NOT IN ('CONFIRMED', 'RECEIVED', 'CANCELLED', 'DELETED', 'OVERDUE')");
            })
            ->count();


        // Contar registrations canceladas
        $canceledRegistrations = (clone $registrationsQuery)
            ->where(function($q) {
                $q->where('payment_status', 'canceled')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CANCELLED', 'DELETED')");
            })
            ->count();


        // Contar registrations vencidas
        $overdueRegistrations = (clone $registrationsQuery)
            ->where(function($q) {
                $q->where('payment_status', 'overdue')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') = 'OVERDUE'");
            })
            ->count();


        // Contar por método de pagamento (apenas pagas)
        $paidByPix = (clone $registrationsQuery)
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CONFIRMED', 'RECEIVED')");
            })
            ->where(function($q) {
                $q->where('payment_method', 'PIX')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.billingType') = 'PIX'");
            })
            ->count();

        $paidByBoleto = (clone $registrationsQuery)
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CONFIRMED', 'RECEIVED')");
            })
            ->where(function($q) {
                $q->where('payment_method', 'BOLETO')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.billingType') = 'BOLETO'");
            })
            ->count();

        $paidByCard = (clone $registrationsQuery)
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CONFIRMED', 'RECEIVED')");
            })
            ->where(function($q) {
                $q->where('payment_method', 'CREDIT_CARD')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.billingType') = 'CREDIT_CARD'");
            })
            ->count();

        // Contar pagas por setor (inscrições individuais - se pedido pago, todas inscrições são pagas)
        $paidBySector = [];
        foreach ($paidPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('sector')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $sector = $reg->sector;
                if (!isset($paidBySector[$sector])) {
                    $paidBySector[$sector] = 0;
                }
                $paidBySector[$sector]++;
            }
        }
        
        // Buscar gratuitas pagas primeiro (para usar em todas as estatísticas)
        $freePaid = (clone $registrationsQuery)
            ->whereNull('asaas_payment_id')
            ->where('payment_status', 'paid')
            ->get();
        
        // Adicionar gratuitas pagas ao setor
        foreach ($freePaid as $reg) {
            if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
            if ($reg->sector) {
                $sector = $reg->sector;
                if (!isset($paidBySector[$sector])) {
                    $paidBySector[$sector] = 0;
                }
                $paidBySector[$sector]++;
            }
        }
        
        arsort($paidBySector);

        // Contar pagas por sexo (inscrições individuais)
        $paidByGender = [];
        foreach ($paidPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $gender = $reg->gender;
                if (!isset($paidByGender[$gender])) {
                    $paidByGender[$gender] = 0;
                }
                $paidByGender[$gender]++;
            }
        }
        
        // Adicionar gratuitas pagas
        foreach ($freePaid as $reg) {
            if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
            $gender = $reg->gender;
            if (!isset($paidByGender[$gender])) {
                $paidByGender[$gender] = 0;
            }
            $paidByGender[$gender]++;
        }

        // Contar pagas por tipo (church_type) - inscrições individuais
        $paidByType = [];
        foreach ($paidPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('church_type')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $type = $reg->church_type;
                if (!isset($paidByType[$type])) {
                    $paidByType[$type] = 0;
                }
                $paidByType[$type]++;
            }
        }
        
        // Adicionar gratuitas pagas
        foreach ($freePaid as $reg) {
            if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
            if ($reg->church_type) {
                $type = $reg->church_type;
                if (!isset($paidByType[$type])) {
                    $paidByType[$type] = 0;
                }
                $paidByType[$type]++;
            }
        }
        
        arsort($paidByType);

        // Contar pagas por congregação com setor (inscrições individuais)
        $paidByCity = [];
        foreach ($paidPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('congregation')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $key = $reg->sector 
                    ? ($reg->sector . ' - ' . $reg->congregation)
                    : $reg->congregation;
                if (!isset($paidByCity[$key])) {
                    $paidByCity[$key] = 0;
                }
                $paidByCity[$key]++;
            }
        }
        
        // Adicionar gratuitas pagas
        foreach ($freePaid as $reg) {
            if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
            if ($reg->congregation) {
                $key = $reg->sector 
                    ? ($reg->sector . ' - ' . $reg->congregation)
                    : $reg->congregation;
                if (!isset($paidByCity[$key])) {
                    $paidByCity[$key] = 0;
                }
                $paidByCity[$key]++;
            }
        }
        
        arsort($paidByCity);

        // Contar NÃO PAGAS por setor (inscrições individuais - excluir as de pedidos pagos)
        $pendingBySector = [];
        // Buscar payment_ids de pedidos não pagos
        $pendingPaymentIds = [];
        if (!empty($allPaymentIdsForCheck)) {
            foreach ($allPaymentIdsForCheck as $paymentId) {
                $firstReg = Registration::where('asaas_payment_id', $paymentId)
                    ->whereBetween('created_at', [$start, $end])
                    ->first();
                
                if (!$firstReg) continue;
                if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;

                $paymentStatus = $this->resolvePaymentStatus($firstReg);
                if ($paymentStatus === 'pending') {
                    $pendingPaymentIds[] = $paymentId;
                }
            }
        }

        foreach ($pendingPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('sector')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $sector = $reg->sector;
                if (!isset($pendingBySector[$sector])) {
                    $pendingBySector[$sector] = 0;
                }
                $pendingBySector[$sector]++;
            }
        }
        
        arsort($pendingBySector);

        // Contar NÃO PAGAS por sexo (inscrições individuais - excluir as de pedidos pagos)
        $pendingByGender = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $gender = $reg->gender;
                if (!isset($pendingByGender[$gender])) {
                    $pendingByGender[$gender] = 0;
                }
                $pendingByGender[$gender]++;
            }
        }

        // Contar NÃO PAGAS por tipo (church_type) - inscrições individuais
        $pendingByType = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('church_type')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $type = $reg->church_type;
                if (!isset($pendingByType[$type])) {
                    $pendingByType[$type] = 0;
                }
                $pendingByType[$type]++;
            }
        }
        
        arsort($pendingByType);

        // Contar NÃO PAGAS por congregação com setor (inscrições individuais)
        $pendingByCity = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $regs = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('congregation')
                ->get();
            
            foreach ($regs as $reg) {
                if ($sectorFilter && $reg->sector !== $sectorFilter) continue;
                $key = $reg->sector 
                    ? ($reg->sector . ' - ' . $reg->congregation)
                    : $reg->congregation;
                if (!isset($pendingByCity[$key])) {
                    $pendingByCity[$key] = 0;
                }
                $pendingByCity[$key]++;
            }
        }
        
        arsort($pendingByCity);

        // ========== ESTATÍSTICAS DE PEDIDOS POR CATEGORIA ==========
        // Contar pedidos pagos por setor (agrupados)
        $ordersPaidBySector = [];
        foreach ($paidPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('sector')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $sector = $firstReg->sector;
            if (!isset($ordersPaidBySector[$sector])) {
                $ordersPaidBySector[$sector] = 0;
            }
            $ordersPaidBySector[$sector]++;
        }
        arsort($ordersPaidBySector);

        // Contar pedidos pagos por sexo (agrupados - usar primeira registration do pedido)
        $ordersPaidByGender = [];
        foreach ($paidPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $gender = $firstReg->gender;
            if (!isset($ordersPaidByGender[$gender])) {
                $ordersPaidByGender[$gender] = 0;
            }
            $ordersPaidByGender[$gender]++;
        }

        // Contar pedidos pagos por tipo (agrupados)
        $ordersPaidByType = [];
        foreach ($paidPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('church_type')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $type = $firstReg->church_type;
            if (!isset($ordersPaidByType[$type])) {
                $ordersPaidByType[$type] = 0;
            }
            $ordersPaidByType[$type]++;
        }
        arsort($ordersPaidByType);

        // Contar pedidos pagos por congregação (agrupados)
        $ordersPaidByCity = [];
        foreach ($paidPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('congregation')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $key = $firstReg->sector 
                ? ($firstReg->sector . ' - ' . $firstReg->congregation)
                : $firstReg->congregation;
            if (!isset($ordersPaidByCity[$key])) {
                $ordersPaidByCity[$key] = 0;
            }
            $ordersPaidByCity[$key]++;
        }
        arsort($ordersPaidByCity);

        // Contar pedidos não pagos por setor
        $ordersPendingBySector = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('sector')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $sector = $firstReg->sector;
            if (!isset($ordersPendingBySector[$sector])) {
                $ordersPendingBySector[$sector] = 0;
            }
            $ordersPendingBySector[$sector]++;
        }
        arsort($ordersPendingBySector);

        // Contar pedidos não pagos por sexo
        $ordersPendingByGender = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $gender = $firstReg->gender;
            if (!isset($ordersPendingByGender[$gender])) {
                $ordersPendingByGender[$gender] = 0;
            }
            $ordersPendingByGender[$gender]++;
        }

        // Contar pedidos não pagos por tipo
        $ordersPendingByType = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('church_type')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $type = $firstReg->church_type;
            if (!isset($ordersPendingByType[$type])) {
                $ordersPendingByType[$type] = 0;
            }
            $ordersPendingByType[$type]++;
        }
        arsort($ordersPendingByType);

        // Contar pedidos não pagos por congregação
        $ordersPendingByCity = [];
        foreach ($pendingPaymentIds as $paymentId) {
            $firstReg = Registration::where('asaas_payment_id', $paymentId)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('congregation')
                ->first();
            
            if (!$firstReg) continue;
            if ($sectorFilter && $firstReg->sector !== $sectorFilter) continue;
            
            $key = $firstReg->sector 
                ? ($firstReg->sector . ' - ' . $firstReg->congregation)
                : $firstReg->congregation;
            if (!isset($ordersPendingByCity[$key])) {
                $ordersPendingByCity[$key] = 0;
            }
            $ordersPendingByCity[$key]++;
        }
        arsort($ordersPendingByCity);

        // Buscar lista de setores únicos das registrations
        $availableSectors = Registration::whereBetween('created_at', [$start, $end])
            ->whereNotNull('sector')
            ->distinct()
            ->orderBy('sector')
            ->pluck('sector')
            ->toArray();

        $usersCount    = User::count();

        // Últimas registrations agrupadas por payment_id
        $latestRegistrations = (clone $registrationsQuery)
            ->select('asaas_payment_id', DB::raw('MIN(created_at) as created_at'))
            ->whereNotNull('asaas_payment_id')
            ->groupBy('asaas_payment_id')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function($group) {
                // Buscar a primeira registration do grupo para obter dados completos
                $registration = Registration::where('asaas_payment_id', $group->asaas_payment_id)
                    ->with('event')
                    ->orderBy('created_at')
                    ->first();
                
                if (!$registration) {
                    return null;
                }

                // Calcular total do pagamento (soma de todas as registrations do mesmo payment_id)
                $allRegs = Registration::where('asaas_payment_id', $group->asaas_payment_id)->get();
                $totalAmount = $allRegs->sum('price_paid');
                
                // Buscar gateway_payload da primeira registration
                $gatewayPayload = $registration->gateway_payload ?? [];
                
                return [
                    'id' => $registration->asaas_payment_id,
                    'payment_id' => $registration->asaas_payment_id,
                    'payment_method' => $registration->payment_method,
                    'payment_status' => $this->resolvePaymentStatus($registration),
                    'total_amount' => $totalAmount,
                    'buyer_name' => $registration->name,
                    'buyer_cpf' => $registration->cpf,
                    'buyer_email' => null,
                    'buyer_phone' => $registration->phone,
                    'gateway_payload' => $gatewayPayload,
                    'registrations_count' => $allRegs->count(),
                    'registrations' => $allRegs->map(fn($r) => [
                        'id' => $r->id,
                        'registration_number' => $r->registration_number,
                        'name' => $r->name,
                        'event' => $r->event,
                    ]),
                    'created_at' => $registration->created_at,
                    'updated_at' => $registration->updated_at,
                ];
            })
            ->filter()
            ->values();

        // Calcular valores baseados em registrations agrupadas por payment_id
        $paymentGroups = (clone $registrationsQuery)
            ->whereNotNull('asaas_payment_id')
            ->select('asaas_payment_id', DB::raw('MIN(created_at) as created_at'))
            ->groupBy('asaas_payment_id')
            ->get();

        $valuePaid = 0;
        $valuePending = 0;
        $valueCanceled = 0;
        $valueOverdue = 0;
        $valueTotal = 0;
        $byPayment = [];

        foreach ($paymentGroups as $group) {
            $regs = Registration::where('asaas_payment_id', $group->asaas_payment_id)->get();
            $firstReg = $regs->first();
            
            if (!$firstReg) continue;

            $paymentStatus = $this->resolvePaymentStatus($firstReg);
            $gatewayPayload = $firstReg->gateway_payload ?? [];
            
            // Calcular valor total do pagamento
            $totalValue = 0;
            if (isset($gatewayPayload['totalValue'])) {
                $totalValue = (float) $gatewayPayload['totalValue'] * 100;
            } elseif (isset($gatewayPayload['value'])) {
                $totalValue = (float) $gatewayPayload['value'] * 100;
            } else {
                $totalValue = $regs->sum('price_paid');
            }

            $valueTotal += $totalValue;

            if ($paymentStatus === 'paid') {
                $valuePaid += $totalValue;
            } elseif ($paymentStatus === 'pending') {
                $valuePending += $totalValue;
            } elseif ($paymentStatus === 'canceled') {
                $valueCanceled += $totalValue;
            } elseif ($paymentStatus === 'overdue') {
                $valueOverdue += $totalValue;
            }

            $paymentMethod = $firstReg->payment_method ?? 'UNKNOWN';
            if (!isset($byPayment[$paymentMethod])) {
                $byPayment[$paymentMethod] = 0;
            }
            $byPayment[$paymentMethod]++;
        }

        return response()->json([
            'stats' => [
                // Relatório por Inscrição (Individual)
                'registrations' => [
                    'total'     => $totalRegistrations,
                    'paid'      => $paidRegistrations,
                    'pending'   => $pendingRegistrations,
                    'canceled'  => $canceledRegistrations,
                    'overdue'   => $overdueRegistrations,
                    'validated' => $validatedRegistrations,
                ],
                // Relatório por Pedido (Agrupado)
                'orders' => [
                    'total'     => $totalOrders,
                    'paid'      => $paidOrders,
                    'pending'   => $pendingOrders,
                    'canceled'  => $canceledOrders,
                    'overdue'   => $overdueOrders,
                ],
                // Mantendo campos antigos para compatibilidade
                'orders_old'        => $totalRegistrations,
                'paid_old'          => $paidRegistrations,
                'pending_old'       => $pendingRegistrations,
                'canceled_old'      => $canceledRegistrations,
                'overdue_old'       => $overdueRegistrations,
                'validated_old'     => $validatedRegistrations,
                // Estatísticas detalhadas (por inscrição)
                'paid_by_pix'   => $paidByPix,
                'paid_by_boleto'=> $paidByBoleto,
                'paid_by_card'  => $paidByCard,
                'paid_by_sector'=> $paidBySector,
                'paid_by_gender'=> $paidByGender,
                'paid_by_type'  => $paidByType,
                'paid_by_city'  => $paidByCity,
                'pending_by_sector'=> $pendingBySector,
                'pending_by_gender'=> $pendingByGender,
                'pending_by_type' => $pendingByType,
                'pending_by_city' => $pendingByCity,
                // Estatísticas detalhadas (por pedido)
                'orders_paid_by_sector'=> $ordersPaidBySector,
                'orders_paid_by_gender'=> $ordersPaidByGender,
                'orders_paid_by_type'  => $ordersPaidByType,
                'orders_paid_by_city'  => $ordersPaidByCity,
                'orders_pending_by_sector'=> $ordersPendingBySector,
                'orders_pending_by_gender'=> $ordersPendingByGender,
                'orders_pending_by_type' => $ordersPendingByType,
                'orders_pending_by_city' => $ordersPendingByCity,
                'users'         => $usersCount,
                'value_paid'    => $valuePaid,
                'value_pending' => $valuePending,
                'value_canceled'=> $valueCanceled,
                'value_overdue' => $valueOverdue,
                'value_total'   => $valueTotal,
                'byPayment'     => $byPayment,
            ],
            'latestOrders'   => $latestRegistrations, // Mantendo nome 'latestOrders' para compatibilidade
            'availableSectors' => $availableSectors,
            'period' => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ]
        ]);
    }

    private function resolvePaymentStatus($registration)
    {
        if ($registration->payment_status === 'paid') {
            return 'paid';
        }

        $gatewayStatus = $registration->gateway_payload['status'] ?? null;
        if ($gatewayStatus === 'CONFIRMED' || $gatewayStatus === 'RECEIVED') {
            return 'paid';
        }
        if ($gatewayStatus === 'PENDING') {
            return 'pending';
        }
        if ($gatewayStatus === 'CANCELLED' || $gatewayStatus === 'DELETED') {
            return 'canceled';
        }
        if ($gatewayStatus === 'OVERDUE') {
            return 'overdue';
        }

        return $registration->payment_status ?? 'pending';
    }
}
