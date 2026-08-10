@php
    $isEdit = isset($category);
    $action = $isEdit
        ? route('erp.ecommerce-product-categories.update', $category->id)
        : route('erp.ecommerce-product-categories.store');

    $mainCategoryOptions = $mainCategoryOptions ?? collect();
    $selectedParentId = old('parent_id', $category->parent_id ?? '');
@endphp

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Validasi gagal',
                html: @json($errors->all()).join('<br>'),
            });
        });
    </script>
@endif

@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: @json(session('error')),
            });
        });
    </script>
@endif

<div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <form action="{{ $action }}" method="POST" id="categoryForm" enctype="multipart/form-data">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    {{-- Penanda buat validasi: main category jadi wajib diisi di form ini. --}}
                    <input type="hidden" name="category_type" value="sub">

                    <div class="card-body">
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="parent_id" class="fw-semibold">Main Category</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id"
                                    name="parent_id">
                                    <option value="">Pilih main category</option>
                                    @foreach ($mainCategoryOptions as $mainCategory)
                                        <option value="{{ $mainCategory->id }}"
                                            {{ (string) $selectedParentId === (string) $mainCategory->id ? 'selected' : '' }}>
                                            {{ $mainCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Sub category ini akan tampil di bawah main category yang
                                    dipilih.</small>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Name</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-tag"></i></div>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $category->name ?? '') }}"
                                        placeholder="Name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="slug" class="fw-semibold">Slug</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-link"></i></div>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" value="{{ old('slug', $category->slug ?? '') }}"
                                        placeholder="slug">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Diisi otomatis dari nama, boleh diubah manual.</small>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-start">
                            <div class="col-lg-2">
                                <label for="description" class="fw-semibold">Description</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <textarea name="description" id="description" rows="5"
                                    class="form-control @error('description') is-invalid @enderror" placeholder="Description">{{ old('description', $category->description ?? '') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-2 align-items-start">
                            <div class="col-lg-2">
                                <label for="image" class="fw-semibold">Image</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <input type="file" class="form-control @error('image') is-invalid @enderror"
                                    id="image" name="image" accept="image/*">
                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                @if ($isEdit && $category->image)
                                    <div class="mt-1" id="old-image-container">
                                        <img src="{{ asset('storage/' . $category->image) }}" alt="Category Image"
                                            style="max-width: 120px; border-radius: 8px;">
                                    </div>
                                @endif

                                <div class="mt-1" id="new-image-container" style="display:none;">
                                    <img id="preview-image" src="#" alt="Preview"
                                        style="max-width: 120px; border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const imageInput = document.getElementById('image');
            const parentSelect = document.getElementById('parent_id');

            function slugify(value) {
                return value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
            }

            nameInput.addEventListener('input', function() {
                slugInput.value = slugify(nameInput.value);
            });

            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('preview-image');
                const previewContainer = document.getElementById('new-image-container');
                const oldImageContainer = document.getElementById('old-image-container');

                if (!file) {
                    previewContainer.style.display = 'none';

                    if (oldImageContainer) {
                        oldImageContainer.style.display = 'block';
                    }

                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';

                    if (oldImageContainer) {
                        oldImageContainer.style.display = 'none';
                    }
                };

                reader.readAsDataURL(file);
            });

            $('#parent_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih main category',
                width: '100%',
                dropdownParent: $('#categoryForm'),
                minimumResultsForSearch: 0,
            });

            $('#categoryForm').on('submit', function(e) {
                $('.invalid-feedback.client-error').remove();
                $('.is-invalid').removeClass('is-invalid');

                let hasError = false;

                if (!parentSelect.value) {
                    hasError = true;
                    parentSelect.classList.add('is-invalid');
                    parentSelect.insertAdjacentHTML('afterend',
                        '<div class="invalid-feedback d-block client-error">Main category wajib dipilih.</div>');
                }

                if (!nameInput.value.trim()) {
                    hasError = true;
                    nameInput.classList.add('is-invalid');
                    nameInput.closest('.input-group').insertAdjacentHTML('beforeend',
                        '<div class="invalid-feedback client-error">Nama sub category wajib diisi.</div>');
                }

                if (hasError) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
