<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        OrderService $orderService
    ): JsonResponse {
        $order = $orderService->createOrder(
            $request->validated('items')
        );

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order->load('items.product'),
        ]);
    }
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => Order::with('items.product')->latest()->get(),
        ]);
    }
}