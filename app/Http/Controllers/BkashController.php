<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BkashController extends Controller
{
    private $grantTokenUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant';
    private $createPaymentUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create';
    private $executePaymentUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute';

    private $appKey = '4f6o0cjiki2rfm34kfdadl1eqq';
    private $appSecret = '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b';
    private $username = 'sandboxTokenizedUser02';
    private $password = 'sandboxTokenizedUser02@12345';
    private $callbackUrl = 'http://localhost:3000/payment/callback';

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

    public function createPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Token generation failed'], 500);
        }

        $invoice = 'INV-' . strtoupper(uniqid());

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-app-key' => $this->appKey,
        ])->post($this->createPaymentUrl, [
                    'mode' => '0011',
                    'payerReference' => 'user123',
                    'callbackURL' => $this->callbackUrl,
                    'amount' => $request->amount,
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $invoice,
                ]);

        $data = $response->json();
        $data['generated_invoice'] = $invoice;

        return response()->json($data);
    }

    public function executePayment($paymentID)
    {
        $token = $this->getToken();


        if (!$token) {
            return response()->json(['error' => 'Token generation failed'], 500);
        }


        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-APP-Key' => $this->appKey,
        ])->post($this->executePaymentUrl . '/' . $paymentID);


        return response()->json($response->json());
    }
}