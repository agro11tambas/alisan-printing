<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\CustomerAccount;
use App\Models\CustomerPasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customer_accounts,email',
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
                'email' => $validated['email'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'],
                'password' => Hash::make($validated['password']),
                'auth_provider' => 'phone',
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
            'whatsapp_number' => ['required', 'string', 'min:9', 'max:20', 'regex:/^08\d+$/'],
            'password' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $validated['whatsapp_number']);
        $phoneCandidates = [$phone];

        if (str_starts_with($phone, '0')) {
            $phoneCandidates[] = '62'.substr($phone, 1);
        }

        $matchingAccounts = CustomerAccount::query()
            ->whereIn('whatsapp_number', array_unique($phoneCandidates))
            ->get()
            ->filter(fn (CustomerAccount $candidate) => $this->passwordMatches(
                $candidate,
                $validated['password'],
                $phone
            ));

        if ($matchingAccounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor HP atau password salah.',
            ], 401);
        }

        $account = $matchingAccounts->firstWhere('is_active', true)
            ?? $matchingAccounts->first();

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

    /**
     * Support legacy ERP accounts whose initial password was generated from
     * the international (628...) version of the phone number.
     */
    private function passwordMatches(CustomerAccount $account, string $password, string $loginPhone): bool
    {
        if (Hash::check($password, $account->password)) {
            return true;
        }

        // Only apply the compatibility check when the customer is using their
        // login phone number as the initial password. Custom passwords remain exact.
        if ($password !== $loginPhone) {
            return false;
        }

        $accountPhone = preg_replace('/\D/', '', (string) $account->whatsapp_number);
        $internationalLoginPhone = str_starts_with($loginPhone, '0')
            ? '62'.substr($loginPhone, 1)
            : $loginPhone;
        $internationalAccountPhone = str_starts_with($accountPhone, '0')
            ? '62'.substr($accountPhone, 1)
            : $accountPhone;

        return $internationalLoginPhone === $internationalAccountPhone
            && Hash::check($internationalAccountPhone, $account->password);
    }

    public function validatePasswordResetToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $resetToken = CustomerPasswordResetToken::query()
            ->where('token_hash', hash('sha256', $validated['token']))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->whereHas('customerAccount', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $resetToken) {
            return response()->json([
                'success' => false,
                'message' => 'Link reset password tidak valid, sudah digunakan, atau sudah kedaluwarsa.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link reset password masih valid.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $wasReset = DB::transaction(function () use ($validated) {
            $resetToken = CustomerPasswordResetToken::query()
                ->where('token_hash', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if (
                ! $resetToken
                || $resetToken->used_at
                || $resetToken->expires_at->isPast()
            ) {
                return false;
            }

            $customerAccount = CustomerAccount::query()
                ->whereKey($resetToken->customer_account_id)
                ->where('is_active', true)
                ->first();

            if (! $customerAccount) {
                return false;
            }

            $customerAccount->update([
                'password' => Hash::make($validated['password']),
                'auth_provider' => 'phone',
            ]);
            $customerAccount->tokens()->delete();

            $resetToken->update([
                'used_at' => now(),
            ]);

            return true;
        });

        if (! $wasReset) {
            return response()->json([
                'success' => false,
                'message' => 'Link reset password tidak valid, sudah digunakan, atau sudah kedaluwarsa.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login menggunakan password baru.',
        ]);
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

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $customerAccount = $request->user();

        if (! Hash::check($validated['current_password'], $customerAccount->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $customerAccount->update([
            'password' => Hash::make($validated['password']),
            'auth_provider' => 'phone',
        ]);

        $currentTokenId = $customerAccount->currentAccessToken()?->id;
        $customerAccount->tokens()
            ->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
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
