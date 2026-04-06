<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Send a welcome email.
     */
    public function sendWelcome(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'user_name' => 'required|string|max:255',
            'message'   => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            Mail::to($request->email)
                ->send(new WelcomeMail(
                    $request->user_name,
                    $request->message ?? 'Welcome to our application!'  // ✅ request field stays the same
                // WelcomeMail now maps it to $body internally
                ));


            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a general/custom email.
     */
    public function sendCustom(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to'       => 'required|email',
            'cc'       => 'nullable|email',
            'bcc'      => 'nullable|email',
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            Mail::send([], [], function ($mail) use ($request) {
                $mail->to($request->to)
                    ->subject($request->subject)
                    ->html($request->body);

                if ($request->cc) {
                    $mail->cc($request->cc);
                }

                if ($request->bcc) {
                    $mail->bcc($request->bcc);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
