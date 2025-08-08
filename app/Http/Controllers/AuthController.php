<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Models\AgentBalance;
use App\Models\AgentDepositRequest;
use App\Models\BetterBalance;
use App\Models\BetterDepositRequest;
use App\Models\EmailVerification;
use App\Models\Orders;
use App\Models\otp;
use App\Models\Product;
use App\Models\User;
use App\Models\WithdrawRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;

class AuthController extends HelperController
{
    //    public function register(Request $request)

    public function register(Request $request)
    {
        try {

            if ($request->email) {
                if (User::where('email', $request->email)->count() > 0) {
                    $this->sendError('Error', "This email already exists.");

                }
            }

            $otp = rand(100000, 999999); // Generate random 6-digit OTP
            $expired = now()->addMinute(2);
            // Check if an OTP already exists for the user, or create a new one
            $existingOtp = Otp::where('email', $request->email)->first();
            if (!$existingOtp) {
                $existingOtp = new Otp();
                $existingOtp->email = $request->email;
            }
            // Update OTP and expiration fields
            $existingOtp->otp = $otp;
            $existingOtp->expired = $expired;
            $existingOtp->save();



            // Prepare email content
            $siteName = "Villagers";
            $subject = "Your $siteName OTP Code";
            $body = "
            Dear Customer,<br><br>
            Thank you for registering at <strong>$siteName</strong>.<br>
            Your One-Time Password (OTP) is: <strong style='font-size:18px;'>$otp</strong>.<br><br>
            This OTP is valid for 2 minutes. Please do not share it with anyone.<br><br>
            Regards,<br>
            The $siteName Team
           ";
            $to = $request->email;
            $result = $this->sendEmail($subject, $body, $to);
            if ($result == "yes") {
                return $this->sendResponse($result, 'Otp send successfully.');
            } else {
                return $this->sendError("Email send fail please try again", "");
            }


        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while processing the registration.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function verifyOtpAndGenerateSession(Request $request)
    {
        try {


            $existingOtp = Otp::where('email', $request->email)->where('otp', $request->otp)->first();
            // Check if the OTP exists and is valid
            if (!$existingOtp) {
                return $this->sendError('Invalid OTP.', 401);
            }
            // Check if the OTP has expired
            if (now()->greaterThan($existingOtp->expired)) {
                return $this->sendError('OTP has expired.', 403);
            }
            $existingOtp->delete();

            if ($request->email) {
                if (User::where('email', $request->email)->count() > 0) {
                    $this->sendError('Error', "This email already exists.");
                }
            }
            // Generate password if not provided
            $password = $request->input('password');
            $hashedPassword = Hash::make($password);
            // Create the user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $hashedPassword,
            ]);
            // Attempt to log in the user with the generated credentials
            $credentials = ['email' => $request->email, 'password' => $password];
            if (!$token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $success = $this->respondWithToken($token);
            return $this->sendResponse([
                'password' => $password,
                'token' => $success,
                "user" => auth()->user(),
            ], 'User created and logged in successfully.');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while processing the registration.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {

        if (!$request->email) {
            $this->sendError('Error', "This email already exists.", code: 203);
        }
        if (!$request->password) {
            return $this->sendError('Credential.', ['error' => 'password is required.']);
        }
        $credentials = $request->only('email', 'password');


        // Attempt to authenticate the user
        if (!$token = auth()->attempt($credentials)) {
            return $this->sendError('Unauthorized.', ['error' => 'Unauthorized.']);
        }

        // Get the authenticated user
        $user = auth()->user();

        // Generate token response
        $success = $this->respondWithToken($token);
        // Construct result array with user and token data
        $result = [
            'user' => $user,
            'token' => $success
        ];
        // Return success response
        return $this->sendResponse($result, 'User login successfully.');
    }


    public function profile(Request $request)
    {
        $user = auth()->user();

        return $this->sendResponse($user, 'User data fetched from database');
    }


    private function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }

    public function getAllCustomer()
    {
        $allCustomers = User::all();
        return $this->sendResponse($allCustomers, "Get all Customers");
    }
    public function dashboard()
    {
        $customers = User::count();
        $totalSales = Orders::count();
        $totalProducts = Product::count();

        // Assuming "recent sales" means last 7 days, adjust as needed
        $totalRecentSales = Orders::where('created_at', '>=', now()->subDays(15))->get();

        $data = [
            'customers' => $customers,
            'totalProducts' => $totalProducts,
            'totalSales' => $totalSales,
            'totalRecentSales' => $totalRecentSales,
        ];

        return $this->sendResponse($data, 'Dashboard data fetched successfully');
    }

}