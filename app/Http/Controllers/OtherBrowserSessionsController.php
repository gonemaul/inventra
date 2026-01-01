<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtherBrowserSessionsController extends Controller
{
    /**
     * Log out from other browser sessions.
     */
    public function destroy(Request $request)
    {
        // 1. Validasi Password
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // 2. Cek apakah password benar
        if (! Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('Password yang Anda masukkan salah.')],
            ]);
        }

        // 3. Fitur Bawaan Laravel: Logout perangkat lain & reset password hash session
        Auth::logoutOtherDevices($request->password);

        // 4. Hapus sesi lain dari tabel database (Pembersihan Manual)
        // Langkah ini memastikan record di tabel sessions benar-benar hilang kecuali yg sekarang.
        if (config('session.driver') === 'database') {
            $this->deleteOtherSessionRecords($request);
        }

        return back(303);
    }

    /**
     * Delete the other browser session records from storage.
     */
    protected function deleteOtherSessionRecords(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
