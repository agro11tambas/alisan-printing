@extends('erp.layouts.main')

{{-- Halaman ini tidak punya tabel maupun dropdown, jadi DataTables, Select2,
     Lightbox, dan Scroller tidak perlu dimuat. Hemat ~175 KB JS + ~60 KB CSS. --}}
@section('assets_mode', 'ringan')

@section('content')
<div class="main-content">
    <div class="container h-100">
        <div class="row justify-content-center align-items-center">
            <h1 class="text-center align-items-center"><span class="text-secondary">Welcome</span></h1>
        </div>
    </div>
</div>
@endsection
