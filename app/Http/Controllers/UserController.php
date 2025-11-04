<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function getUsers()
    {
        return view('erp.pages.users.users');
    }

    public function dataUsers(Request $request)
    {
        $users = User::latest();

        if ($request->has('name')) {
            $users->where('name', 'like', '%' . $request->name . '%');
        }

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('name', function ($user) {
                return $user->name;
            })
            ->addColumn('username', function ($user) {
                return $user->username;
            })
            ->addColumn('role', function ($user) {
                return $user->role;
            })
            ->addColumn('action', function ($user) {
                return view('erp.pages.users.partials.action-button', compact('user'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $permissions = Permission::with('subItems')->get();
        return view('erp.pages.users.create-user', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'required|alpha_dash|unique:users,username',
            'password'    => 'required|min:8',
            'role'        => 'required|string',
            'permissions' => 'array',
            'permission_sub_items'   => 'array',
        ], [
            'name.required'     => 'Nama harus diisi',
            'username.required' => 'Username harus diisi',
            'username.unique'   => 'Username sudah digunakan',
            'password.required' => 'Password harus diisi',
            'password.min'      => 'Password minimal 8 karakter',
            'role.required'     => 'Role harus diisi',
        ]);

        // 🔹 AUTO TAMBAHKAN PARENT PERMISSION JIKA ADA SUB-PERMISSION YANG DICENTANG
        if ($request->filled('permission_sub_items')) {
            $parentIds = \App\Models\PermissionSubItem::whereIn('id', $request->permission_sub_items)
                ->pluck('permission_id')
                ->unique()
                ->toArray();

            // gabungkan parent permission dengan yang dicentang manual
            $mergedPermissions = array_unique(array_merge($request->permissions ?? [], $parentIds));
            $request->merge(['permissions' => $mergedPermissions]);
        }

        // 🔹 BUAT USER
        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->username . '@example.com',
            'password' => bcrypt($request->password),
            'role'     => $request->role,
        ]);

        // 🔹 SIMPAN RELASI
        $user->permissions()->sync($request->permissions ?? []);
        $user->permissionSubItems()->sync($request->permission_sub_items ?? []);

        return redirect('/erp/shop-manager/users')->with('success', 'User berhasil dibuat');
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->role === 'Owner') {
            return redirect()->back()
                ->with('error', 'Owner tidak dapat dihapus.');
        }

        $user->permissions()->detach(); // hapus semua relasi permission
        $user->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus');
    }

    public function edit($id)
    {
        $user = User::with('permissions')->findOrFail($id);
        $permissions = Permission::with('subItems')->get();

        return view('erp.pages.users.edit-user', [
            "user" => $user,
            "permissions" => $permissions
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'username'               => [
                'required',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($id),
            ],
            'password'               => 'nullable|min:8',
            'role' => 'required|string|in:Owner,Admin,Kurir',
            'permissions'            => 'array',
            'permission_sub_items'   => 'array',
        ], [
            'name.required'     => 'Nama harus diisi',
            'username.required' => 'Username harus diisi',
            'username.unique'   => 'Username sudah digunakan',
            'password.min'      => 'Password minimal 8 karakter',
            'role.required'     => 'Role harus diisi',
        ]);

        $username = $request->input('username');
        $email = $username . '@example.com';

        $user = User::findOrFail($id);

        $user->name = $request->input('name');
        $user->username = $username;
        $user->email = $email;
        $user->role = $request->input('role');

        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        $user->save();

        // update parent permissions
        $user->permissions()->sync($request->permissions ?? []);

        // update sub permissions
        $user->permissionSubItems()->sync($request->permission_sub_items ?? []);

        return redirect('/erp/shop-manager/users')->with('success', 'Data berhasil diperbarui');
    }
}
