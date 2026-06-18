<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login', [
            "title" => "Masuk"
        ]);
    }

    public function authenticate(LoginRequest $request)
    {
        $remember = $request->boolean('remember');
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials, $remember)) { // login gagal
            request()->session()->regenerate();
            $user = auth()->user();

            $data = [
                "success" => true,
                "redirect_to" => $user->must_change_password
                    ? '/force-change-password'
                    : ($user->isUser() ? '/' : '/dashboard'),
                "message" => $user->must_change_password
                    ? "Silakan ganti password terlebih dahulu."
                    : "Login berhasil, silahkan tunggu!"
            ];
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($data);
            }

            return response("", 302)->header("Location", $data["redirect_to"]);
        }

        $data = [
            "success" => false,
            "message" => "Login gagal, silahkan coba lagi!"
        ];
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($data)->setStatusCode(400);
        }

        return back()->withErrors([
            'email' => $data['message'],
        ])->onlyInput('email');
    }

    public function logout()
    {
        auth()->logout();

        request()->session()->regenerate();
        request()->session()->regenerateToken();

        return redirect()->route('auth.login')->with('success', 'Anda berhasil keluar.');
    }
}
