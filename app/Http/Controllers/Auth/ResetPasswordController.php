<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\AuthTraits;
use App\Traits\BaseTraits;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ResetPasswordController extends Controller
{
    use BaseTraits, AuthTraits;

    public function index(Request $request)
    {
        try {
            $email = $request->input('email');
            $token_str = $request->input('v_token');

            $token = $this->getResetTokenByEmail($email, $token_str);

            if (!$token) {
                throw new Exception('Invalid token', Response::HTTP_BAD_REQUEST);
            }

            $is_expired = $this->checkTokenExpiry($token);

            if ($is_expired) {
                throw new Exception('Token has expired, request a new one', Response::HTTP_BAD_REQUEST);
            }

            return view('auth.reset-password')->with([
                'token' => $token_str,
                'email' => $email
            ]);
        } catch (\Exception $error) {
            return view('errors.default')->with(['message'=>$error->getMessage()]);
        }

    }
}
