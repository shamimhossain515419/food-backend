<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;

class HelperController extends Controller
{
    public function sendResponse($result, $message)
    {
        $response = [
            'status' => true,
            'data' => $result,
            'message' => $message,
        ];
        return response()->json($response, 200);
    }

    public function sendError($error, $errorMessage = [], $code = 203)
    {
        $response = [
            'status' => false,
            'message' => $error,
        ];
        if (!empty($errorMessage)) {
            $response['data'] = $errorMessage;
        }
        return response()->json($response, $code);
    }


    public function sendEmail($subject, $body, $to)
    {
        $data['mail_body'] = $body;
        $data['name'] = "viliagers";
        $data['contact_email'] = env('MAIL_FROM_ADDRESS');
        $data['to_email'] = $to;
        $data['subject'] = $subject;

        Mail::send(['html' => 'emails.signup'], $data, function ($message) use ($data) {
            $message->to($data['to_email']);
            $message->subject($data['subject']);
            $message->replyTo($data['contact_email']);
        });

        return "yes";
    }

}