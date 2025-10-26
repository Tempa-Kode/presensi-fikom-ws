<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;

class ProfilController extends Controller
{
    /**
     * Update Foto Profil.
     *
     * Mengupdate foto profil pengguna.
     *
     * @param string $nidn
     * @return Response.
     */
    public function updateFotoProfil(Request $request)
    {
        $validasi = $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            $user = $request->user();

            // Hapus foto lama jika ada
            if ($user->foto && file_exists(public_path($user->foto))) {
                unlink(public_path($user->foto));
            }

            // Upload foto baru
            $file = $request->file('foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/foto');

            // Buat folder jika belum ada
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            // Update path di database
            $user->foto = 'uploads/foto/' . $filename;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diupdate',
                'data' => [
                    'foto' => asset($user->foto)
                ]
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate foto profil',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
