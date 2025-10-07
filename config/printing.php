<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nama Printer Default
    |--------------------------------------------------------------------------
    | Atur nama printer yang akan dipakai di server.
    | - Linux: pakai nama printer dari `lpstat -p -d`
    | - Windows: pakai share name printer, contoh: \\SERVER\LX310
    */

    'win_share'     => env('PRINTER_WIN', '\\IDEAPAD\\LX310'),

];
