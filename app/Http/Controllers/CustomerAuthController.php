<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CustomerAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.customer-login');
    }

    public function showRegistrationForm()
    {
        return view('auth.customer-register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        // Try login with email or phone
        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        if (Auth::attempt([$loginField => $request->input('login'), 'password' => $request->input('password')], $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('catalog.index'))
                ->with('success', 'Selamat datang kembali!');
        }

        return back()->withErrors([
            'login' => 'Email/Telepon atau password salah.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('catalog.index')
            ->with('success', 'Anda telah logout.');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
            'role' => 'customer',
        ]);

        Auth::login($user);

        return redirect()->route('catalog.index')
            ->with('success', 'Registrasi berhasil! Selamat datang di CocoPop!');
    }
}
