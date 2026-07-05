<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <meta name="author" content="WRAPCODERS">

    <title>Alisan</title>
    <link rel="shortcut icon" type="image/x-icon" href="#">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}">
</head>

<body>
    <main class="auth-minimal-wrapper">
        <div class="auth-minimal-inner">
            <div class="minimal-card-wrapper">
                <div class="card mb-2 mt-3 mx-2 mx-sm-0 position-relative">
                    <div class="wd-50 bg-white p-1 rounded-circle shadow-lg position-absolute translate-middle top-0 start-50">
                        <img src="{{ asset('storage/uploads/products/default.png') }}" alt="" class="img-fluid">
                    </div>
                    <div class="card-body p-sm-5">
                        <h2 class="fs-20 fw-bolder mb-2">Login</h2>
                        <form action="/login" method="POST" class="w-100 mt-2 pt-1">
                            @csrf
                            @method('POST')
                            <div class="mb-2">
                                <input type="text" class="form-control" placeholder="Username" name="username" id="username" required>
                            </div>
                            <div class="mb-2">
                                <input type="password" class="form-control" placeholder="Password" name="password" id="password" required>
                            </div>
                            <div class="mt-0">
                                <button type="submit" class="btn btn-lg btn-primary w-100">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>
</body>

</html>
