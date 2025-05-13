<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\OrdersLog;
use App\Models\User;
use Illuminate\Http\Request;

class OrdersController extends HelperController
{
    public function index()
    {
        $orders = Orders::orderBy("id", "desc")->get();
        return $this->sendResponse($orders, "message get success");
    }

    public function show(Request $request, $id)
    {
        $order = Orders::find($id);
        $orderDetails = OrdersLog::where("order_id", $order->id)->get();
        $customer = User::find($order->customer_id);
        $data = [
            "order" => $order,
            "orderDetails" => $orderDetails,
            "customer" => $customer
        ];
        return $this->sendResponse($data, "message get success");
    }

    public function update(Request $request, $id)
    {
        $order = Orders::find($id);
        $status = $request->status;
        if ($status) {
            $order->status = $status;

        }
        $order->save();
        return $this->sendResponse($order, "order status change success");
    }

    public function store(Request $request)
    {
        try {
            $products = $request->products;

            $user = auth()->user();


            // Check if customer_id is missing
            if (!$user->id) {
                return $this->sendError("Customer id is not found");
            }

            // Check if products is an array and not empty
            if (!is_array($products) || count($products) <= 0) {
                return $this->sendError("Product list is required");
            }

            // Generate a unique transaction ID
            $transaction_id = uniqid('txn_');

            // Create order
            $order = Orders::create([
                "name" => $request->name,
                "mobile" => $request->mobile,
                "customer_id" => $user->id,
                "address" => $request->address,
                "total_price" => $request->total_price,
                "quantity" => $request->quantity,
                "transaction_id" => $transaction_id,
                "status" => "0"
            ]);

            // Save order logs
            if ($order) {
                foreach ($products as $product) {
                    OrdersLog::create([
                        "name" => $product['name'],
                        "price" => $product['price'],
                        "quantity" => $product['stock'],
                        "photo" => $product['photo'],
                        "order_id" => $order->id,
                        "product_id" => $product['id'],
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'transaction_id' => $transaction_id,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage());
        }
    }



    public function getSingleUserAllOrder()
    {
        try {
            $user = auth()->user();
            $allOrder = Orders::where("customer_id", $user->id)->get();
            return $this->sendResponse($allOrder, "Order report get");
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage());
        }
    }


}