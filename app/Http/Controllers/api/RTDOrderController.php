<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RTD\ConfirmPaymentRequest;
use App\Http\Requests\RTD\CreateOrderRequest;
use App\Http\Requests\RTD\DispatchOrderRequest;
use App\Http\Resources\RTD\RtdOrderResource;
use App\Exceptions\RTDDomainException;
use App\Services\RTDOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class RTDOrderController extends Controller
{
    public function __construct(
        protected RTDOrderService $orderService,
    ) {
    }

    public function requestOrder(CreateOrderRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('rtd/logos', 'public');
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

    public function markInProduction(int $id)
    {
        try {
            $order = $this->orderService->markInProduction($id, request()->user()->id);

            return Response::success('Marked as in production', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function dispatch(DispatchOrderRequest $request, int $id)
    {
        try {
            $proofData = [
                'proof_type' => $request->validated()['proof_type'],
                'file_path'  => '',
            ];

            if ($request->hasFile('file')) {
                $proofData['file_path'] = $request->file('file')->store('rtd/dispatch-proofs', 'public');
            } elseif ($request->filled('tracking_number')) {
                $proofData['file_path'] = $request->validated()['tracking_number'];
            }

            $order = $this->orderService->markDispatched($id, $proofData, $request->user()->id);

            return Response::success('Order dispatched', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function confirmDelivery(int $id)
    {
        try {
            $order = $this->orderService->confirmDelivery($id, request()->user()->id);

            return Response::success('Delivery confirmed', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function raiseDispute(int $id)
    {
        try {
            $order = $this->orderService->raiseDispute($id, request()->user()->id);

            return Response::success('Dispute raised', new RtdOrderResource($order));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
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
