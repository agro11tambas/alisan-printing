@php
    $isEdit = isset($category);
    $action = $isEdit
        ? route('erp.ecommerce-product-categories.update', $category->id)
        : route('erp.ecommerce-product-categories.store');

    $subcategoryOptions = $subcategoryOptions ?? [];

    // Sub category yang sudah nempel di main category ini. Kalau habis gagal
    // validasi, dipakai lagi dari old() supaya pilihan user tidak hilang.
    $oldChildIds = old('existing_child_ids');

    if (is_array($oldChildIds)) {
        $oldChildNames = old('existing_children', []);

        $selectedChildren = collect($oldChildIds)
            ->map(function ($childId) use ($oldChildNames, $subcategoryOptions) {
                $childId = (int) $childId;
                $option = collect($subcategoryOptions)->firstWhere('id', $childId);

                return [
                    'id' => $childId,
                    'name' => $oldChildNames[$childId]['name'] ?? ($option['name'] ?? ''),
                ];
            });
    } else {
        $selectedChildren = $isEdit
            ? $category->children->map(fn($child) => ['id' => $child->id, 'name' => $child->name])
            : collect();
    }

    $newSubcategories = collect(old('subcategories', []))->values();

    if ($newSubcategories->isEmpty()) {
        $newSubcategories = collect([['name' => '', 'description' => '']]);
    }

    // Kalau error validasinya dari bagian sub category, tab sub yang dibuka duluan.
    $subTabActive = collect($errors->keys())->contains(
        fn($key) => \Illuminate\Support\Str::startsWith($key, ['subcategories', 'existing_child']),
    );
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
                        <ul class="nav nav-tabs mb-3" id="categoryFormTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $subTabActive ? '' : 'active' }}" id="main-category-form-tab"
                                    data-bs-toggle="tab" data-bs-target="#main-category-form-pane" type="button"
                                    role="tab" aria-controls="main-category-form-pane"
                                    aria-selected="{{ $subTabActive ? 'false' : 'true' }}">
                                    Main Category
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $subTabActive ? 'active' : '' }}" id="sub-category-form-tab"
                                    data-bs-toggle="tab" data-bs-target="#sub-category-form-pane" type="button"
                                    role="tab" aria-controls="sub-category-form-pane"
                                    aria-selected="{{ $subTabActive ? 'true' : 'false' }}">
                                    Sub Category
                                    @if ($selectedChildren->isNotEmpty())
                                        <span
                                            class="badge bg-soft-primary text-primary ms-1">{{ $selectedChildren->count() }}</span>
                                    @endif
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="categoryFormTabContent">
                        <div class="tab-pane fade {{ $subTabActive ? '' : 'show active' }}" id="main-category-form-pane"
                            role="tabpanel" aria-labelledby="main-category-form-tab" tabindex="0">
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
                                <small class="text-muted">Form ini untuk main category. Sub category-nya diatur di tab
                                    Sub Category.</small>
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
                                        <img src="{{ $category->image_url }}" alt="Category Image"
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

                        <div class="tab-pane fade {{ $subTabActive ? 'show active' : '' }}" id="sub-category-form-pane"
                            role="tabpanel" aria-labelledby="sub-category-form-tab" tabindex="0">
                        <div class="row mb-2 align-items-start">
                            <div class="col-lg-2">
                                <label class="fw-semibold">Choose Existing Sub Category:</label>
                            </div>
                            <div class="col-lg-10">
                                <select id="existing_subcategory_picker" class="form-select"
                                    data-select2-selector="tag">
                                    <option value="">Choose existing category</option>
                                    @foreach ($subcategoryOptions as $option)
                                        <option value="{{ $option['id'] }}" data-name="{{ $option['name'] }}">
                                            {{ $option['name'] }}{{ $option['parent_name'] ? ' — sub dari ' . $option['parent_name'] : '' }}
                                        </option>
                                    @endforeach
                                </select>

                                <small class="text-muted">
                                    Pilih ini kalau category-nya sudah pernah dibuat. Category yang dipilih akan
                                    dipindah jadi sub category di sini.
                                </small>

                                <div id="selectedSubcategoryList" class="mt-2">
                                    @foreach ($selectedChildren as $child)
                                        <div class="selected-subcategory-item border rounded p-1 mb-1">
                                            <div class="row align-items-center">
                                                <div class="col-lg-10">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-tag"></i></div>
                                                        <input type="text"
                                                            class="form-control selected-subcategory-name"
                                                            name="existing_children[{{ $child['id'] }}][name]"
                                                            value="{{ $child['name'] }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-lg-2 d-flex gap-1">
                                                    <button type="button"
                                                        class="btn btn-warning btn-sm btn-edit-selected-subcategory">
                                                        <i class="feather-edit"></i>
                                                    </button>

                                                    <button type="button"
                                                        class="btn btn-danger btn-sm btn-remove-selected-subcategory">
                                                        <i class="feather-x"></i>
                                                    </button>
                                                </div>

                                                <input type="hidden" name="existing_child_ids[]"
                                                    value="{{ $child['id'] }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-start">
                            <div class="col-lg-2">
                                <label class="fw-semibold">Sub Category Baru:</label>
                            </div>
                            <div class="col-lg-10">
                                <div id="subcategories">
                                    @foreach ($newSubcategories as $index => $subcategory)
                                        <div class="subcategory-item mb-1 row">
                                            <div class="col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-tag"></i></div>
                                                    <input type="text" class="form-control subcategory-name"
                                                        name="subcategories[{{ $index }}][name]"
                                                        value="{{ $subcategory['name'] ?? '' }}"
                                                        placeholder="Sub Category Name">
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-align-left"></i>
                                                    </div>
                                                    <input type="text" class="form-control"
                                                        name="subcategories[{{ $index }}][description]"
                                                        value="{{ $subcategory['description'] ?? '' }}"
                                                        placeholder="Description (optional)">
                                                </div>
                                            </div>

                                            <div class="col-lg-3">
                                                <input type="file" class="form-control"
                                                    name="subcategories[{{ $index }}][image]"
                                                    accept="image/*">
                                            </div>

                                            <div class="col-lg-1 d-flex">
                                                <button type="button"
                                                    class="btn btn-danger btn-remove-subcategory">
                                                    <i class="feather-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-success mt-1" id="add-subcategory">
                                    <i class="feather-plus"></i> Add Sub Category
                                </button>

                                <div>
                                    <small class="text-muted">Slug sub category dibuat otomatis dari namanya.</small>
                                </div>
                            </div>
                        </div>
                        </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('modals')
    <div class="modal fade" id="deleteSubcategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Hapus Baris Sub Category?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus baris sub category ini?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteSubcategoryBtn" class="btn btn-danger">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSelectedSubcategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Lepas Sub Category?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Sub category ini akan dilepas dari main category dan kembali jadi main category sendiri
                    (datanya tidak dihapus).
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteSelectedSubcategoryBtn" class="btn btn-danger">
                        Yes, Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

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

                    // Errornya di tab main, jadi tabnya dibuka biar kelihatan
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('main-category-form-tab')).show();
                    nameInput.focus();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            let subcategoryIndex = {{ $newSubcategories->count() }};

            const subcategoriesWrapper = document.getElementById('subcategories');

            document.getElementById('add-subcategory').addEventListener('click', function() {
                const wrapper = document.createElement('div');
                wrapper.className = 'subcategory-item mb-1 row';

                wrapper.innerHTML = `
                    <div class="col-lg-4">
                        <div class="input-group">
                            <div class="input-group-text"><i class="feather-tag"></i></div>
                            <input type="text"
                                class="form-control subcategory-name"
                                name="subcategories[${subcategoryIndex}][name]"
                                placeholder="Sub Category Name">
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="input-group">
                            <div class="input-group-text"><i class="feather-align-left"></i></div>
                            <input type="text"
                                class="form-control"
                                name="subcategories[${subcategoryIndex}][description]"
                                placeholder="Description (optional)">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <input type="file"
                            class="form-control"
                            name="subcategories[${subcategoryIndex}][image]"
                            accept="image/*">
                    </div>

                    <div class="col-lg-1 d-flex">
                        <button type="button" class="btn btn-danger btn-remove-subcategory">
                            <i class="feather-x"></i>
                        </button>
                    </div>
                `;

                subcategoriesWrapper.appendChild(wrapper);
                subcategoryIndex++;
            });

            let subcategoryToDelete = null;

            subcategoriesWrapper.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-subcategory');
                if (!btn) return;

                subcategoryToDelete = btn.closest('.subcategory-item');

                const modalEl = document.getElementById('deleteSubcategoryModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            document.getElementById('confirmDeleteSubcategoryBtn').addEventListener('click', function() {
                if (subcategoryToDelete) {
                    subcategoryToDelete.remove();
                    subcategoryToDelete = null;
                }

                const modalEl = document.getElementById('deleteSubcategoryModal');
                bootstrap.Modal.getInstance(modalEl).hide();
            });

            $('#existing_subcategory_picker').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose existing category',
                width: '100%',
                dropdownParent: $('#categoryForm'),
                minimumResultsForSearch: 0,
                allowClear: true,
            });

            $('#existing_subcategory_picker').on('change', function() {
                const subcategoryId = $(this).val();

                if (!subcategoryId) return;

                const alreadySelected = document.querySelector(
                    `input[name="existing_child_ids[]"][value="${subcategoryId}"]`
                );

                if (alreadySelected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sudah dipilih',
                        text: 'Sub category ini sudah ada di list.',
                    });

                    $(this).val('').trigger('change');
                    return;
                }

                const subcategoryName = $('#existing_subcategory_picker option:selected').data('name') ?? '-';

                const item = `
                    <div class="selected-subcategory-item border rounded p-1 mb-1">
                        <div class="row align-items-center">
                            <div class="col-lg-10">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-tag"></i></div>
                                    <input type="text"
                                        class="form-control selected-subcategory-name"
                                        name="existing_children[${subcategoryId}][name]"
                                        value="${subcategoryName}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-lg-2 d-flex gap-1">
                                <button type="button" class="btn btn-warning btn-sm btn-edit-selected-subcategory">
                                    <i class="feather-edit"></i>
                                </button>

                                <button type="button" class="btn btn-danger btn-sm btn-remove-selected-subcategory">
                                    <i class="feather-x"></i>
                                </button>
                            </div>

                            <input type="hidden" name="existing_child_ids[]" value="${subcategoryId}">
                        </div>
                    </div>
                `;

                $('#selectedSubcategoryList').append(item);

                $(this).val('').trigger('change');
            });

            let selectedSubcategoryToDelete = null;

            $('#selectedSubcategoryList').on('click', '.btn-remove-selected-subcategory', function() {
                selectedSubcategoryToDelete = $(this).closest('.selected-subcategory-item');

                const modalEl = document.getElementById('deleteSelectedSubcategoryModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            $('#confirmDeleteSelectedSubcategoryBtn').on('click', function() {
                if (selectedSubcategoryToDelete) {
                    selectedSubcategoryToDelete.remove();
                    selectedSubcategoryToDelete = null;
                }

                const modalEl = document.getElementById('deleteSelectedSubcategoryModal');
                bootstrap.Modal.getInstance(modalEl).hide();
            });

            $('#selectedSubcategoryList').on('click', '.btn-edit-selected-subcategory', function() {
                const nameInput = $(this).closest('.selected-subcategory-item')
                    .find('.selected-subcategory-name');

                const isReadOnly = nameInput.prop('readonly');

                nameInput.prop('readonly', !isReadOnly);

                if (isReadOnly) {
                    $(this)
                        .removeClass('btn-warning')
                        .addClass('btn-success')
                        .html('<i class="feather-check"></i>');

                    nameInput.focus();
                } else {
                    $(this)
                        .removeClass('btn-success')
                        .addClass('btn-warning')
                        .html('<i class="feather-edit"></i>');
                }
            });

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')?.focus();
                }, 50);
            });

            // Select2 diinit waktu tabnya masih hidden, lebarnya perlu dibetulkan
            // begitu tab sub category dibuka.
            document.getElementById('sub-category-form-tab').addEventListener('shown.bs.tab', function() {
                $('#existing_subcategory_picker').next('.select2-container').css('width', '100%');
            });
        });
    </script>
@endpush
