{{--
    Pratinjau foto dokumen yang sudah tersimpan, dipakai di form edit Purchase
    List. Tanpa ini user tidak tahu fotonya sudah ada dan mengira wajib unggah
    ulang setiap kali mengedit.
--}}
@if (!empty($path))
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ asset($path) }}" data-lightbox="{{ $group }}">
            <img src="{{ asset($path) }}" alt="{{ $alt }}"
                style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; object-position: center;">
        </a>
        <small class="text-muted">Foto sekarang. Unggah foto baru untuk menggantinya.</small>
    </div>
@endif
