<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DosenController extends Controller
{
    /**
     * menampilkan seluruh daftar dosen
     */
    public function index()
    {
        $data = User::where('role', 'dosen')->oldest()->get();
        return view('dosen.index', compact('data'));
    }

    /**
     * menampilkan form untuk menambahkan data baru
     */
    public function create()
    {
        return view('dosen.create');
    }

    /**
     * menyimpan data dosen baru
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'nidn' => 'required|unique:users,nidn',
            'nama' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'konfirmasi_password' => 'required|same:password',
            'foto' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $validasi['role'] = 'dosen';
            $validasi['password'] = bcrypt($validasi['password']);

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/foto'), $filename);
                $validasi['foto'] = 'uploads/foto/' . $filename;
            }

            User::create($validasi);
            DB::commit();
            return redirect()
                ->route('data.dosen')
                ->with('success', 'Data dosen berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(
                [
                    'error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
                ]
            )->withInput();
        }
    }

    /**
     * maneampilkan form untuk mengedit data dosen
     */
    public function edit($id)
    {
        try {
            $data = User::where('id', $id)->firstOrFail();
            return view('dosen.edit', compact('data'));
        } catch (\Exception $e) {
            return back()->withErrors(
                [
                    'error' => 'Data tidak ditemukan: ' . $e->getMessage()
                ]
            );
        }
    }

    /**
     * mengupdate data dosen
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'nidn' => 'required|unique:users,nidn,' . $id,
            'nama' => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'konfirmasi_password' => 'nullable|same:password',
            'foto' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
            if (!empty($validasi['password'])) {
                $validasi['password'] = bcrypt($validasi['password']);
            } else {
                unset($validasi['password']);
            }
            
            $dosen = User::findOrFail($id);

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/foto'), $filename);
                $validasi['foto'] = 'uploads/foto/' . $filename;

                if ($dosen->foto && file_exists(public_path($dosen->foto))) {
                    unlink(public_path($dosen->foto));
                }
            }

            $dosen->update($validasi);
            DB::commit();
            return redirect()
                ->route('data.dosen')
                ->with('success', 'Data dosen berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(
                [
                    'error' => 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage()
                ]
            )->withInput();
        }
    }

    /**
     * menghapus data dosen
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $dosen = User::findOrFail($id);
            if ($dosen->foto && file_exists(public_path($dosen->foto))) {
                unlink(public_path($dosen->foto));
            }
            $dosen->delete();
            DB::commit();
            return redirect()
                ->route('data.dosen')
                ->with('success', 'Data dosen berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(
                [
                    'error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
                ]
            );
        }
    }
}
