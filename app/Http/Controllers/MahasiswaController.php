<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MahasiswaController extends Controller
{
    /**
     * menampilkan seluruh daftar mahasiswa
     */
    public function index()
    {
        $data = User::where('role', 'mahasiswa')->oldest()->get();
        return view('mahasiswa.index', compact('data'));
    }

    /**
     * menampilkan form untuk menambahkan data baru
     */
    public function create()
    {
        $prodi = Prodi::all();
        return view('mahasiswa.create', compact('prodi'));
    }

    /**
     * menyimpan data dosen baru
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'prodi_id' => 'required|exists:prodi,id',
            'npm' => 'required|unique:users,npm',
            'nama' => 'required|string|max:100',
            'stambuk' => 'required|string|max:4',
            'password' => 'required|string|min:6',
            'konfirmasi_password' => 'required|same:password',
            'foto' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $validasi['role'] = 'mahasiswa';
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
                ->route('data.mahasiswa')
                ->with('success', 'Data mahasiswa berhasil ditambahkan.');
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
     * menampilkan form untuk mengedit data mahasiswa
     */
    public function edit($id)
    {
        try {
            $data = User::where('id', $id)->firstOrFail();
            $prodi = Prodi::all();
            return view('mahasiswa.edit', compact('data', 'prodi'));
        } catch (\Exception $e) {
            return back()->withErrors(
                [
                    'error' => 'Data tidak ditemukan: ' . $e->getMessage()
                ]
            );
        }
    }

    /**
     * mengupdate data mahasiswa
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'prodi_id' => 'required|exists:prodi,id',
            'npm' => 'required|unique:users,npm,' . $id,
            'nama' => 'required|string|max:100',
            'stambuk' => 'required|string|max:4',
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

            $mahasiswa = User::findOrFail($id);

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/foto'), $filename);
                $validasi['foto'] = 'uploads/foto/' . $filename;

                if ($mahasiswa->foto && file_exists(public_path($mahasiswa->foto))) {
                    unlink(public_path($mahasiswa->foto));
                }
            }

            $mahasiswa->update($validasi);
            DB::commit();
            return redirect()
                ->route('data.mahasiswa')
                ->with('success', 'Data mahasiswa berhasil diupdate.');
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
     * Menghapus data mahasiswa
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $mahasiswa = User::findOrFail($id);
            if ($mahasiswa->foto && file_exists(public_path($mahasiswa->foto))) {
                unlink(public_path($mahasiswa->foto));
            }
            $mahasiswa->delete();
            DB::commit();
            return redirect()
                ->route('data.mahasiswa')
                ->with('success', 'Data mahasiswa berhasil dihapus.');
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
