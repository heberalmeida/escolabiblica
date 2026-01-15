<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AsaasTokenizeController;
use App\Http\Controllers\OrderQueryController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariantImageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\QRCodeValidationController;
use App\Http\Controllers\ChurchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function () {
    return response()->json([
        'message' => 'API Laravel + Asaas integrada',
        'version' => '1.0.0',
        'status'  => 'OK',
    ]);
});

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('/auth/check-token', [AuthController::class, 'checkToken']);
    });

    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/catalog/{id}', [CatalogController::class, 'show']);

    Route::post('/checkout', [CheckoutController::class, 'createOrder']);
    Route::post('/checkout/events', [CheckoutController::class, 'createEventRegistrations']);
    Route::post('/payments/asaas/tokenize', [AsaasTokenizeController::class, 'tokenize']);
    Route::post('/payments/installments', [CheckoutController::class, 'simulateInstallments']);
    Route::get('/orders/by-cpf/{cpf}', [OrderQueryController::class, 'byCpf']);
    Route::get('/orders/by-number/{orderNumber}', [OrderQueryController::class, 'byNumber']);

    Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
        Route::put('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
        Route::apiResource('/admin/products', ProductController::class);
        Route::delete('/admin/product-images/{id}', [ProductController::class, 'destroyImage']);
        Route::delete('/admin/variants/{id}', [ProductController::class, 'destroyVariant']);
        Route::delete('/admin/variant-images/{id}', [VariantImageController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'role:admin|supervisor'])->group(function () {
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    });

    Route::get('/cep/{cep}', [CepController::class, 'show']);

    // Eventos e Inscrições
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::get('/churches', [ChurchController::class, 'index']);
    
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::get('/registrations', [RegistrationController::class, 'index']);
    Route::get('/registrations/{id}', [RegistrationController::class, 'show']);
    Route::get('/registrations/qr/{qrCode}', [RegistrationController::class, 'getByQrCode']);
    Route::get('/registrations/phone/{phone}', [RegistrationController::class, 'getByPhone']);
    Route::get('/registrations/by-cpf/{cpf}', [RegistrationController::class, 'getByCpf']);

    // Validação de QR Code
    Route::post('/validate/qrcode', [QRCodeValidationController::class, 'validateByQrCode']);
    Route::post('/validate/name', [QRCodeValidationController::class, 'validateByName']);
    Route::post('/validate/phone', [QRCodeValidationController::class, 'validateByPhone']);

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::apiResource('/admin/events', EventController::class);
        Route::get('/admin/registrations', [RegistrationController::class, 'index']);
        Route::put('/admin/registrations/{id}/mark-as-paid', [RegistrationController::class, 'markAsPaid']);
    });
});
