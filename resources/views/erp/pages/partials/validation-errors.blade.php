{{--
    Menampilkan pesan validasi yang dikirim balik server.

    Tanpa ini, form yang ditolak validasi hanya tampak seperti halaman ter-refresh
    tanpa penjelasan apa pun — penyebab paling sering: foto waybill melebihi batas
    upload PHP, sehingga seluruh isi form ikut ditolak.
--}}
@if ($errors->any())
    <div class="alert alert-danger mx-2 mt-2">
        <div class="fw-semibold mb-1">Data belum tersimpan. Periksa lagi:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Data belum tersimpan',
                html: @json(implode('<br>', $errors->all())),
            });
        });
    </script>
@endif
