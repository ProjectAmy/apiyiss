<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WalimuridProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    // ============================
    // 1. CHECK USER (POST)
    // URL: /api/auth/check
    // Body: { "google_token": "..." }
    // ============================
    public function check(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
        ]);

        $payload = $this->verifyGoogleToken($request->google_token);

        if (!$payload || is_string($payload)) {
            return response()->json([
                'message' => 'Invalid Google Token',
                'debug' => is_string($payload) ? $payload : 'Verification returned false',
            ], 401);
        }

        $email = $payload['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            $roles = $user->getRoleNames();
            $user->load('walimuridProfile');

            return response()->json([
                'message' => 'User found',
                'user' => $user,
                'roles' => $roles,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // ============================
    // 2. REGISTER USER (POST)
    // URL: /api/auth/register
    // Body: { "google_token": "...", "phone": "...", ... }
    // ============================
    public function register(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
            'fullname' => 'nullable|string',
            'shortname' => 'nullable|string',
            'call_name' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $payload = $this->verifyGoogleToken($request->google_token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid Google Token',
            ], 401);
        }

        $email = $payload['email'];
        // Use provided fullname or fallback to Google name
        $name = $request->fullname ?? $payload['name'];

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        // Create User
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(16)), // Random password for Google users
        ]);

        // Assign Role (Default to Wali Murid)
        $user->assignRole('Wali Murid');

        // Create Profile
        $profile = WalimuridProfile::create([
            'user_id' => $user->id,
            'fullname' => $name,
            'shortname' => $request->shortname,
            'call_name' => $request->call_name,
            'phone' => $this->formatPhoneNumber($request->phone),
            'address' => $request->address,
        ]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;
        $roles = $user->getRoleNames();
        $user->load('walimuridProfile');

        return response()->json([
            'message' => 'User successfully registered.',
            'user' => $user,
            'roles' => $roles,
            'token' => $token,
        ], 201);
    }

    // ============================
    // 3. CHECK KEUANGAN (POST)
    // URL: /api/keuangan/auth/check
    // Body: { "google_token": "..." }
    // ============================
    public function checkKeuangan(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
        ]);

        $payload = $this->verifyGoogleToken($request->google_token);

        if (!$payload || is_string($payload)) {
            return response()->json([
                'message' => 'Invalid Google Token',
                'debug' => is_string($payload) ? $payload : 'Verification returned false',
            ], 401);
        }

        $email = $payload['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            // Check if user has role 'Staff Keuangan'
            if (!$user->hasRole('Staff Keuangan')) {
                return response()->json([
                    'message' => 'Unauthorized. You are not Staff Keuangan.',
                ], 403);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            $roles = $user->getRoleNames();
            // Optional: Load specific staff profile if exists in future
            // $user->load('staffProfile');

            return response()->json([
                'message' => 'Staff Keuangan found',
                'user' => $user,
                'roles' => $roles,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // ============================
    // 4. CHECK ADMIN (POST)
    // URL: /api/admin/auth/check
    // Body: { "google_token": "..." }
    // ============================
    public function checkAdmin(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
        ]);

        $payload = $this->verifyGoogleToken($request->google_token);

        if (!$payload || is_string($payload)) {
            return response()->json([
                'message' => 'Invalid Google Token',
                'debug' => is_string($payload) ? $payload : 'Verification returned false',
            ], 401);
        }

        $email = $payload['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            // Check if user has role 'Admin' (or 'admin' for compatibility)
            if (!$user->hasRole(['Admin', 'admin'])) {
                return response()->json([
                    'message' => 'Unauthorized. You are not an Admin.',
                ], 403);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            $roles = $user->getRoleNames();

            return response()->json([
                'message' => 'Admin found',
                'user' => $user,
                'roles' => $roles,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    /**
     * Verify Google ID Token
     *
     * @param string $token
     * @return array|false|string
     */
    private function verifyGoogleToken($token)
    {
        // --- TEMPORARY BYPASS FOR TESTING ---
        // Jika token adalah string pendek yang mengandung '@' (bukan JWT panjang), anggap itu email.
        // Ini untuk memudahkan testing manual di Postman tanpa token asli.
        // Check environment AND explicit bypass flag (default to true in local/testing)
        $shouldBypass = app()->environment(['local', 'testing']) && env('GOOGLE_AUTH_BYPASS', true);

        if ($shouldBypass && strpos($token, '@') !== false && strlen($token) < 200) {
            return [
                'email' => $token,
                'name' => 'Test User (' . $token . ')',
                'sub' => 'bypass_sub_' . $token,
                'picture' => null,
            ];
        }

        try {
            $clientId = env('GOOGLE_CLIENT_ID');
            if (!$clientId) {
                // If GOOGLE_CLIENT_ID is not set, we can't verify properly.
                // However, detailed error logs might safely expose this to developer only.
                throw new \Exception('GOOGLE_CLIENT_ID not set in .env');
            }

            // Path to cacert.pem for local development if needed
            $certPath = storage_path('app/cacert.pem');

            $clientConfig = ['client_id' => $clientId];

            // Use custom Guzzle client with cert ONLY if needed (e.g. local environment issues)
            // Ideally, production servers have proper CA certs.
            if (file_exists($certPath)) {
                $guzzleClient = new \GuzzleHttp\Client([
                    'verify' => $certPath,
                ]);
                $client = new \Google\Client($clientConfig);
                $client->setHttpClient($guzzleClient);
            } else {
                $client = new \Google\Client($clientConfig);
            }

            // Fix for clock skew: "Cannot handle token with iat prior to..."
            JWT::$leeway = 60;

            $payload = $client->verifyIdToken($token);

            if ($payload) {
                return $payload;
            } else {
                // Verification failed (invalid signature etc)
                return false;
            }
        } catch (\Throwable $e) {
            // Return error string for debugging
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Format phone number to start with 62
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 62
        if (strpos($phone, '0') === 0) {
            $phone = '62' . substr($phone, 1);
        }

        // If starts with 8, prepend 62 (handling cases where user enters 812...)
        if (strpos($phone, '8') === 0) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
