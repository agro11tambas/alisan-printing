<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EcommerceInformation;

class EcommerceInformationController extends Controller
{
    public function index()
    {
        $information = EcommerceInformation::first();
        return response()->json([
            'success' => true,
            'data' => $information
        ]);
    }
}
