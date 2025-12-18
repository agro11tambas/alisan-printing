@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Edit Shop Manager</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Shop Manager</li>
                <li class="breadcrumb-item">Edit</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/shop-manager/users" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="ShopManagerForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Shop Manager</span>
                    </button>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/shop-manager/update/{{ $user->id }}" method="POST" id="ShopManagerForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $user->name) }}"
                                        placeholder="Name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="username" class="fw-semibold">Username:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                                        id="username" name="username" value="{{ old('username', $user->username) }}"
                                        placeholder="Username">
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="password" class="fw-semibold">Password:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password"
                                        placeholder="Kosongkan jika tidak ingin mengubah password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="role" class="fw-semibold">Role:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-select @error('role') is-invalid @enderror"
                                        data-select2-selector="tag" id="role" name="role">
                                        <option value="Owner" {{ old('role', $user->role) == 'Owner' ? 'selected' : '' }}>
                                            Owner</option>
                                        <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>
                                            Admin</option>
                                        <option value="Kurir" {{ old('role', $user->role) == 'Kurir' ? 'selected' : '' }}>
                                            Kurir</option>
                                        <option value="Sales" {{ old('role', $user->role) == 'Sales' ? 'selected' : '' }}>
                                            Sales</option>
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Hak Akses:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="row">
                                        @php
                                            $userPermissions = $user->permissions->pluck('id')->toArray();
                                            $userSubPermissions = $user->permissionSubItems->pluck('id')->toArray();
                                        @endphp

                                        @foreach ($permissions as $permission)
                                            <div class="col-md-4 mb-3">
                                                <div class="card p-2 h-100">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input parent-permission" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="perm_{{ $permission->id }}"
                                                            {{ in_array($permission->id, old('permissions', $userPermissions)) ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold"
                                                            for="perm_{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                    @if ($permission->subItems->count())
                                                        <div class="ms-3">
                                                            @foreach ($permission->subItems as $sub)
                                                                <div class="form-check">
                                                                    <input class="form-check-input sub-permission"
                                                                        type="checkbox" name="permission_sub_items[]"
                                                                        value="{{ $sub->id }}"
                                                                        id="subperm_{{ $sub->id }}"
                                                                        data-parent="perm_{{ $permission->id }}"
                                                                        {{ in_array($sub->id, old('permission_sub_items', $userSubPermissions)) ? 'checked' : '' }}
                                                                        {{ in_array($permission->id, old('permissions', $userPermissions)) ? '' : 'disabled' }}>
                                                                    <label class="form-check-label"
                                                                        for="subperm_{{ $sub->id }}">
                                                                        {{ $sub->name }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.parent-permission').forEach(function(parentCheckbox) {
                parentCheckbox.addEventListener('change', function() {
                    let parentId = parentCheckbox.id;
                    let subItems = document.querySelectorAll('.sub-permission[data-parent="' +
                        parentId + '"]');

                    subItems.forEach(function(sub) {
                        sub.disabled = !parentCheckbox.checked;
                        if (!parentCheckbox.checked) {
                            sub.checked = false;
                        }
                    });
                });
            });
        });
    </script>
@endpush
