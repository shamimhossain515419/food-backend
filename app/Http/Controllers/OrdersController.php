<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\OrdersLog;
use App\Models\User;
use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
class OrdersController extends HelperController
{

    private $grantTokenUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant';
    private $createPaymentUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create';
    private $executePaymentUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute';

    private $appKey = '4f6o0cjiki2rfm34kfdadl1eqq';
    private $appSecret = '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b';
    private $username = 'sandboxTokenizedUser02';
    private $password = 'sandboxTokenizedUser02@12345';
    private $callbackUrl = 'http://localhost:3000/payment/callback';

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
            $paymentMethod = $request->paymentMethod;

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
            $transaction_id = 'INV-' . strtoupper(uniqid());

            // Create order
            $order = Orders::create([
                "name" => $request->name,
                "mobile" => $request->mobile,
                "customer_id" => $user->id,
                "address" => $request->address,
                "payment_id" => null,
                "total_price" => $request->total_price,
                "payment_type" => $paymentMethod == "bkash" ? "2" : "1",
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
            if ($paymentMethod == "bkash") {
                $token = $this->getToken();
                $response = Http::withHeaders([
                    'authorization' => $token,
                    'x-app-key' => $this->appKey,
                ])->post($this->createPaymentUrl, [
                            'mode' => "0011",
                            'payerReference' => $request->name,
                            'callbackURL' => $this->callbackUrl,
                            'amount' => $request->total_price,
                            'currency' => 'BDT',
                            'intent' => 'sale',
                            'merchantInvoiceNumber' => $transaction_id,
                        ]);
                $data = $response->json();
                if (isset($data['paymentID'])) {
                    $order->payment_id = $data['paymentID'];
                    $order->save();
                } else {
                    return $this->sendError("Failed to initialize bKash payment", $data);
                }
                $order->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'transaction_id' => $transaction_id,
                    'payment_type' => $paymentMethod,
                    'data' => $data
                ]);

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

    public function paymentOrderConfirm(Request $request)
    {
        try {
            $order = Orders::where("payment_id", $request->payment_id)->first();

            if (!$order) {
                return $this->sendError("Order not found or already deleted.");
            }

            if ($request->status === 'success') {
                $order->payment_status = "2"; // Mark as paid
                $order->save();

                return $this->sendResponse($order, "Payment successful");
            }

            // If payment failed or status not 'success', delete order and logs
            OrdersLog::where('order_id', $order->id)->delete();
            $order->delete();

            return $this->sendResponse(null, "Payment failed, order deleted");

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

    private function getToken()
    {
        if (Cache::has('bkash_token')) {
            return Cache::get('bkash_token');
        }

        $response = Http::withHeaders([
            'username' => $this->username,
            'password' => $this->password,
        ])->post($this->grantTokenUrl, [
                    'app_key' => $this->appKey,
                    'app_secret' => $this->appSecret,
                ]);

        $data = $response->json();

        if (isset($data['id_token'])) {
            Cache::put('bkash_token', $data['id_token'], now()->addMinutes(55));
            return $data['id_token'];
        }

        return null;
    }


}