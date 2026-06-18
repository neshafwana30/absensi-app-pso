<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForcePasswordController extends Controller
{
    public function index()
    {
        return view('auth.force-change-password', [
            'title' => 'Ganti Password'
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $user = auth()->user();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        session()->flash('success', 'Password berhasil diperbarui.');

        return response('', 302)->header(
            'Location',
            $user->isUser() ? '/' : '/dashboard'
        );
    }
}
