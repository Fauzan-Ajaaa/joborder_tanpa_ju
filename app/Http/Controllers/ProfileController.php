<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's company information.
     */
    public function updatePerusahaan(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_perusahaan'      => ['nullable', 'string', 'max:255'],
            'alamat_perusahaan'    => ['nullable', 'string', 'max:500'],
            'phone_perusahaan'     => ['nullable', 'string', 'max:50'],
            'email_perusahaan'     => ['nullable', 'email', 'max:100'],
            'deskripsi_perusahaan' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $user->nama_perusahaan      = $request->nama_perusahaan;
        $user->alamat_perusahaan    = $request->alamat_perusahaan;
        $user->phone_perusahaan     = $request->phone_perusahaan;
        $user->email_perusahaan     = $request->email_perusahaan;
        $user->deskripsi_perusahaan = $request->deskripsi_perusahaan;
        $user->save();

        // Sync with Company model if exists
        if ($user->company_id) {
            $company = \App\Models\Company::find($user->company_id);
            if ($company) {
                $company->update([
                    'name' => $request->nama_perusahaan,
                    'address' => $request->alamat_perusahaan,
                    'phone' => $request->phone_perusahaan,
                    'email' => $request->email_perusahaan,
                ]);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'perusahaan-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
