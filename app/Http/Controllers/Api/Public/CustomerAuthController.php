<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customer_accounts,email',
            'whatsapp_number' => 'required|string|max:20|unique:customer_accounts,whatsapp_number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return DB::transaction(function () use ($validated) {
            $customer = Customers::create([
                'name' => $validated['name'],
                'phone' => $validated['whatsapp_number'],
                'customer_deposit' => 0,
            ]);

            $account = CustomerAccount::create([
                'customer_id' => $customer->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'password' => Hash::make($validated['password']),
                'auth_provider' => 'manual',
                'is_active' => true,
                'last_login_at' => now(),
            ]);

            $account->customers()->syncWithoutDetaching([$customer->id]);

            $token = $account->createToken('customer-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Register success.',
                'data' => [
                    'token' => $token,
                    'customer' => $account->load(['customer', 'customers']),
                ],
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = CustomerAccount::where('email', $validated['identifier'])
            ->orWhere('whatsapp_number', $validated['identifier'])
            ->first();

        if (! $account || ! Hash::check($validated['password'], $account->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email/WhatsApp atau password salah.',
            ], 401);
        }

        if (! $account->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun customer tidak aktif.',
            ], 403);
        }

        $account->update([
            'last_login_at' => now(),
        ]);

        $token = $account->createToken('customer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login success.',
            'data' => [
                'token' => $token,
                'customer' => $account->load(['customer', 'customers']),
            ],
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        $account = DB::transaction(function () use ($googleUser) {
            $account = CustomerAccount::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($account) {
                $account->update([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'auth_provider' => $account->auth_provider ?? 'google',
                    'last_login_at' => now(),
                ]);

                if ($account->customer_id) {
                    $account->customers()->syncWithoutDetaching([$account->customer_id]);
                }

                return $account;
            }

            $customer = Customers::create([
                'name' => $googleUser->getName(),
                'phone' => 'google_' . $googleUser->getId(),
                'customer_deposit' => 0,
            ]);

            $account = CustomerAccount::create([
                'customer_id' => $customer->id,
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
                'whatsapp_number' => null,
                'password' => null,
                'auth_provider' => 'google',
                'is_active' => true,
                'last_login_at' => now(),
            ]);

            $account->customers()->syncWithoutDetaching([$customer->id]);

            return $account;
        });

        $token = $account->createToken('customer-token')->plainTextToken;

        $frontendUrl = config('app.frontend_website_url', env('FRONTEND_WEBSITE_URL', 'http://localhost:3000'));

        return redirect()->away(
            $frontendUrl . '/auth/callback?token=' . urlencode($token)
        );
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer profile retrieved successfully.',
            'data' => $request->user()->load(['customer', 'customers']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout success.',
        ]);
    }
}
