<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nama_perusahaan' => ['nullable', 'string', 'max:255'],
            'alamat_perusahaan' => ['nullable', 'string', 'max:500'],
        ]);

        // Sederhanakan: Gunakan nama perusahaan default jika kosong
        $namaPerusahaan = $request->nama_perusahaan ?: 'Manufaktur ' . $request->name;
        $alamatPerusahaan = $request->alamat_perusahaan ?: 'Alamat Belum Diatur';

        $company = \App\Models\Company::create([
            'name' => $namaPerusahaan,
            'address' => $alamatPerusahaan,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nama_perusahaan' => $namaPerusahaan,
            'alamat_perusahaan' => $alamatPerusahaan,
            'company_id' => $company->id,
        ]);

        // Inisialisasi data dasar (Tetap digunakan agar aplikasi tidak error, 
        // tapi ini sudah di-scope per-company sehingga tetap "kosong" bagi user baru)
        \App\Services\TenantInitializer::initialize($company);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
