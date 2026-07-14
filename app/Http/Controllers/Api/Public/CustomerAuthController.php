<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_number' => 'required|string|min:9|max:20',
        ]);

        $phone = $validated['whatsapp_number'];
        $otp = rand(100000, 999999);

        Cache::put('otp_' . $phone, $otp, now()->addMinutes(5));

        $url = config('whatsapp.fonnte.url');
        $token = config('whatsapp.fonnte.api_key');

        if ($token && $token !== 'isi_dengan_token_api_disini') {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $token
                ])->post($url, [
                    'target' => $phone,
                    'message' => "Kode OTP Anda untuk login ke Alisan adalah: *$otp*. Kode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapapun.",
                ]);

                if (!$response->successful()) {
                    Log::error('WhatsApp OTP Send Error', ['response' => $response->body()]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengirim OTP ke WhatsApp. Coba lagi nanti.',
                    ], 500);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp OTP Send Exception', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke server WhatsApp. Coba lagi nanti.',
                ], 500);
            }
        } else {
            Log::info("DEVELOPMENT OTP generated for {$phone}: {$otp}");
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke nomor WhatsApp Anda.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_number' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        $phone = $validated['whatsapp_number'];
        $inputOtp = $validated['otp'];

        $cachedOtp = Cache::get('otp_' . $phone);

        if (!$cachedOtp || $cachedOtp != $inputOtp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid atau sudah kedaluwarsa.',
            ], 400);
        }

        Cache::forget('otp_' . $phone);

        $account = CustomerAccount::where('whatsapp_number', $phone)->first();

        if ($account && !$account->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun customer tidak aktif.',
            ], 403);
        }

        return DB::transaction(function () use ($phone, $account) {
            if (!$account) {
                $customer = Customers::create([
                    'name' => 'User ' . substr($phone, -4),
                    'phone' => $phone,
                    'customer_deposit' => 0,
                ]);

                $account = CustomerAccount::create([
                    'customer_id' => $customer->id,
                    'name' => 'User ' . substr($phone, -4),
                    'whatsapp_number' => $phone,
                    'email' => null,
                    'password' => null,
                    'auth_provider' => 'otp',
                    'is_active' => true,
                    'last_login_at' => now(),
                ]);

                $account->customers()->syncWithoutDetaching([$customer->id]);
            } else {
                $account->update([
                    'last_login_at' => now(),
                ]);
            }

            $token = $account->createToken('customer-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login sukses.',
                'data' => [
                    'token' => $token,
                    'customer' => $account->load(['customer', 'customers']),
                ],
            ]);
        });
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
            'data' => $request->user()->load(['customer', 'customers.addresses']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20|unique:customer_accounts,whatsapp_number,' . $user->id,
        ]);

        $user->update([
            'name' => $validated['name'],
            'whatsapp_number' => $validated['whatsapp_number'],
        ]);

        // Also update the linked customer records if they exist
        foreach ($user->customers as $customer) {
            $customer->update([
                'name' => $validated['name'],
                'phone' => $validated['whatsapp_number'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->load(['customer', 'customers.addresses']),
        ]);
    }

    public function createBusiness(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $phone = $validated['phone'] ?? null;

        // If phone is provided, check if it already exists
        if ($phone) {
            $existingCustomer = Customers::where('phone', $phone)->first();
            if ($existingCustomer) {
                // Link the existing customer to this account instead of creating a duplicate
                $user->customers()->syncWithoutDetaching([$existingCustomer->id]);

                if (!$user->customer_id) {
                    $user->update(['customer_id' => $existingCustomer->id]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Bisnis berhasil ditambahkan.',
                    'data' => $user->load(['customer', 'customers.addresses']),
                ]);
            }
        }

        $customer = Customers::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'customer_deposit' => 0,
        ]);

        $user->customers()->syncWithoutDetaching([$customer->id]);

        if (!$user->customer_id) {
            $user->update(['customer_id' => $customer->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bisnis berhasil ditambahkan.',
            'data' => $user->load(['customer', 'customers.addresses']),
        ]);
    }

    public function createAddress(Request $request, $customerId)
    {
        $user = $request->user();
        
        if (!$user->customers()->where('customers.id', $customerId)->exists() && $user->customer_id != $customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'address' => 'required|string',
            'google_maps' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $customer = Customers::findOrFail($customerId);

        $address = $customer->addresses()->create([
            'business_name' => $validated['business_name'] ?? null,
            'address' => $validated['address'],
            'google_maps' => $validated['google_maps'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil ditambahkan.',
            'data' => $user->load(['customer', 'customers.addresses']),
        ]);
    }

    public function updateAddress(Request $request, $id)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'address' => 'required|string',
            'google_maps' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $address = \App\Models\CustomerAddresses::findOrFail($id);

        $customerId = $address->customer_id;
        if (!$user->customers()->where('customers.id', $customerId)->exists() && $user->customer_id != $customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $address->update([
            'business_name' => $validated['business_name'] ?? null,
            'address' => $validated['address'],
            'google_maps' => $validated['google_maps'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $user->load(['customer', 'customers.addresses']),
        ]);
    }

    public function deleteAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = \App\Models\CustomerAddresses::findOrFail($id);

        $customerId = $address->customer_id;
        if (!$user->customers()->where('customers.id', $customerId)->exists() && $user->customer_id != $customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dihapus.',
            'data' => $user->load(['customer', 'customers.addresses']),
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
