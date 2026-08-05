@php
    $isEdit = isset($category);
    $action = $isEdit
        ? route('erp.ecommerce-product-categories.update', $category->id)
        : route('erp.ecommerce-product-categories.store');
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

                    <div class="card-body">
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Name</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-layers"></i></div>
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
                                <label for="parent_id" class="fw-semibold">Parent Category</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-git-merge"></i></div>
                                    <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id"
                                        name="parent_id">
                                        <option value="">-- Tanpa parent (kategori utama) --</option>
                                        @foreach ($parentOptions ?? [] as $option)
                                            <option value="{{ $option['id'] }}"
                                                @selected((string) old('parent_id', $category->parent_id ?? '') === (string) $option['id'])>
                                                {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $option['depth']) !!}{{ $option['depth'] > 0 ? '└ ' : '' }}{{ $option['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Kosongkan kalau ini kategori level teratas.</small>
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

            $('#categoryForm').on('submit', function(e) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback.client-error').remove();

                if (!nameInput.value.trim()) {
                    e.preventDefault();
                    nameInput.classList.add('is-invalid');
                    nameInput.closest('.input-group').insertAdjacentHTML('beforeend',
                        '<div class="invalid-feedback client-error">Nama category wajib diisi.</div>');
                }
            });
        });
    </script>
@endpush
