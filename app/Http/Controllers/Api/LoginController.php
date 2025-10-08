<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\DosenResource;
use App\Http\Resources\MahasiswaResource;
use Illuminate\Support\Facades\Auth;
use Dedoc\Scramble\Attributes\BodyParameter;

class LoginController extends Controller
{
     /**
     * Login Pengguna.
     *
     * @param Request $request
     * @return Response.
     *
     * @unauthenticated
     */
    #[BodyParameter('credential', 'NIDN atau NPM pengguna', required: true, example: '0114046501')]
    #[BodyParameter('password', 'Password pengguna', required: true, example: '0114046501')]
    public function __invoke(Request $request)
    {
        $credentials = $request->validate([
            'credential' => 'required|string',
            'password' => 'required|string'
        ]);

        try{
            // Coba login dengan NIDN (Dosen)
             $loginData = [
                'nidn' => $credentials['credential'],
                'password' => $credentials['password']
            ];
            if (Auth::attempt($loginData)) {
                $data = Auth::user();
                return (new DosenResource(true, 'Login berhasil', $data))
                    ->additional(['token' => $data->createToken('api-token')->plainTextToken])
                    ->response()
                    ->setStatusCode(200);
            }

            // Jika gagal, coba login dengan NPM (Mahasiswa)
            $loginData = [
                'npm' => $credentials['credential'],
                'password' => $credentials['password']
            ];
            if (Auth::attempt($loginData)) {
                $data = Auth::user();
                return (new MahasiswaResource(true, 'Login berhasil', $data))
                    ->additional(['token' => $data->createToken('api-token')->plainTextToken])
                    ->response()
                    ->setStatusCode(200);
            }

            return response()->json([
                'status' => false,
                'message' => 'NIDN/NPM atau password salah'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
