<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RTD\ConfirmPaymentRequest;
use App\Http\Requests\RTD\CreateOrderRequest;
use App\Http\Requests\RTD\VerifyRtdRazorpayPaymentRequest;
use App\Http\Resources\RTD\RtdOrderResource;
use App\Exceptions\RazorpayDomainException;
use App\Exceptions\RTDDomainException;
use App\Services\RtdOrderRazorpayPaymentService;
use App\Services\RTDOrderService;
use App\Support\RtdPublicUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class RTDOrderController extends Controller
{
    public function __construct(
        protected RTDOrderService $orderService,
        protected RtdOrderRazorpayPaymentService $rtdRazorpayPaymentService,
    ) {
    }

    public function requestOrder(CreateOrderRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $data['logo_path'] = RtdPublicUpload::store(
                    $request->file('logo'),
                    RtdPublicUpload::DIR_LOGOS
                );
            }

            $order = $this->orderService->createOrderRequest($data, $request->user()->id);

            return Response::success(
                'Order request placed',
                new RtdOrderResource($order),
                null,
                HttpResponse::HTTP_CREATED
            );
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function accept(int $id)
    {
        try {
            $order = $this->orderService->acceptOrder($id, request()->user()->id);

            return Response::success('Order accepted', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function decline(int $id)
    {
        try {
            $order = $this->orderService->declineOrder($id, request()->user()->id);

            return Response::success('Order declined', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function confirmPayment(ConfirmPaymentRequest $request)
    {
        // Allow connecting without Razorpay when direct-confirm is enabled OR payments
        // are globally off (free-launch mode).
        $freeMode = ! config('features.payments_enabled', true);
        if (! config('rtd.allow_direct_confirm_payment', false) && ! $freeMode) {
            return Response::error(
                'Direct payment confirmation is disabled. Complete payment through Razorpay checkout.',
                null,
                HttpResponse::HTTP_FORBIDDEN
            );
        }

        try {
            $order = $this->orderService->confirmPayment(
                $request->validated()['order_id'],
                $request->user()->id
            );

            return Response::success('Payment confirmed', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function createRazorpayOrder(int $id)
    {
        try {
            $payload = $this->rtdRazorpayPaymentService->createOrderForRtdOrder(
                (int) request()->user()->id,
                $id
            );

            return Response::success('Order created', $payload, null, HttpResponse::HTTP_CREATED);
        } catch (RazorpayDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function verifyRazorpayPayment(VerifyRtdRazorpayPaymentRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $order = $this->rtdRazorpayPaymentService->verifyAndFulfill(
                (int) $request->user()->id,
                $id,
                $data['razorpay_order_id'],
                $data['razorpay_payment_id'],
                $data['razorpay_signature'],
            );

            return Response::success('Payment verified', new RtdOrderResource($order));
        } catch (RazorpayDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function cancel(int $id)
    {
        try {
            $order = $this->orderService->cancelOrder($id, request()->user()->id);

            return Response::success('Order cancelled', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function myOrders(Request $request)
    {
        try {
            $user = $request->user();
            $role = $user->role ?? 'brand';

            $orders = $this->orderService->getMyOrders(
                $user->id,
                $role,
                $request->only(['status', 'per_page'])
            );

            return Response::success('My orders', RtdOrderResource::collection($orders));
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function show(int $id)
    {
        try {
            $order = $this->orderService->getOrderDetail($id, request()->user()->id);

            return Response::success('Order detail', new RtdOrderResource($order));
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_NOT_FOUND);
        }
    }
}
