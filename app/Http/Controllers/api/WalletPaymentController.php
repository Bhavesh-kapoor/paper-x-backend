<?php

namespace App\Http\Controllers\api;

use App\Exceptions\RazorpayDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\CreateRazorpayExactCreditsOrderRequest;
use App\Http\Requests\Wallet\CreateRazorpayOrderRequest;
use App\Http\Requests\Wallet\VerifyRazorpayPaymentRequest;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class WalletPaymentController extends Controller
{
    public function __construct(
        protected RazorpayPaymentService $service,
    ) {}

    public function createOrder(CreateRazorpayOrderRequest $request)
    {
        try {
            $payload = $this->service->createOrderForPack(
                (int) $request->user()->id,
                (int) $request->validated()['credit_pack_id'],
            );

            return Response::success('Order created', $payload, null, HttpResponse::HTTP_CREATED);
        } catch (RazorpayDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('rzp.create_order_failed', ['err' => $e->getMessage()]);

            return Response::error('Failed to create payment order', null, 500);
        }
    }

    public function createExactCreditsOrder(CreateRazorpayExactCreditsOrderRequest $request)
    {
        try {
            $payload = $this->service->createOrderForExactCredits(
                (int) $request->user()->id,
                (int) $request->validated()['credits'],
            );

            return Response::success('Order created', $payload, null, HttpResponse::HTTP_CREATED);
        } catch (RazorpayDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (QueryException $e) {
            Log::error('rzp.create_exact_credits_order_db', [
                'err' => $e->getMessage(),
            ]);
            $sql = strtolower($e->getMessage());
            if (str_contains($sql, 'credit_pack_id') || str_contains($sql, 'null') || str_contains($sql, 'not null')) {
                return Response::error(
                    'Exact-credit payments require database migration 2026_05_11_000001. Run: php artisan migrate',
                    ['code' => 'EXACT_CREDITS_MIGRATION_REQUIRED'],
                    HttpResponse::HTTP_FAILED_DEPENDENCY
                );
            }

            return Response::error('Failed to create payment order', null, 500);
        } catch (\Throwable $e) {
            Log::error('rzp.create_exact_credits_order_failed', [
                'err' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return Response::error('Failed to create payment order', null, 500);
        }
    }

    public function verify(VerifyRazorpayPaymentRequest $request)
    {
        try {
            $order = $this->service->verifyAndFulfill(
                (int) $request->user()->id,
                $request->validated()['razorpay_order_id'],
                $request->validated()['razorpay_payment_id'],
                $request->validated()['razorpay_signature'],
            );

            return Response::success('Payment verified', [
                'transaction_id' => $order->walletTransaction?->transaction_id,
                'credits_added' => $order->credits,
                'new_balance' => (float) ($order->user->wallet?->balance ?? 0),
                'amount_paid' => $order->amount_paise / 100,
            ]);
        } catch (RazorpayDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('rzp.verify_failed', ['err' => $e->getMessage()]);

            return Response::error('Payment verification failed', null, 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $this->service->handleWebhookEvent(
                $request->getContent(),
                (string) $request->header('X-Razorpay-Signature', ''),
            );

            return response()->json(['ok' => true]);
        } catch (RazorpayDomainException $e) {
            Log::warning('rzp.webhook_domain', ['message' => $e->getMessage(), 'code' => $e->getStatusCode()]);

            return response()->json(['ok' => false], $e->getStatusCode() >= 400 ? $e->getStatusCode() : 400);
        } catch (\Throwable $e) {
            Log::error('rzp.webhook_failed', ['err' => $e->getMessage()]);

            return response()->json(['ok' => false], 400);
        }
    }
}
