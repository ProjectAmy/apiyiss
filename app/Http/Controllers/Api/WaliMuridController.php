<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaliMurid;

class WaliMuridController extends Controller
{
    // ============================
    // 1. CHECK USER (POST)
    // URL: /api/walimurid/check
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
        $wali = WaliMurid::where('email', $email)->first();

        if ($wali) {
            // Generate token
            $token = $wali->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User ditemukan',
                'data' => $wali,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'User tidak ditemukan',
        ], 404);
    }

    // ============================
    // 2. REGISTER USER (POST)
    // URL: /api/walimurid/register
    // Body: { "google_token": "...", "phone": "...", ... }
    // ============================
    public function register(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
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
        $name = $payload['name'];

        // Check if email already exists
        if (WaliMurid::where('email', $email)->exists()) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        $wali = WaliMurid::create([
            'name' => $name,
            'email' => $email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        // Generate token
        $token = $wali->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User berhasil didaftarkan.',
            'data' => $wali,
            'token' => $token,
        ], 201);
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
        // Jika token berisi '@', anggap itu email yang valid
        if (strpos($token, '@') !== false) {
            return [
                'email' => $token,
                'name' => 'Test User (' . $token . ')',
                'sub' => 'bypass_sub',
                'picture' => null,
            ];
        }

        // Default mock user jika token bukan email
        return [
            'email' => 'parent@example.com',
            'name' => 'Parent User',
            'sub' => 'bypass_sub_default',
            'picture' => null,
        ];

        /* 
        // ORIGINAL GOOGLE VERIFICATION CODE
        try {
            $clientId = env('GOOGLE_CLIENT_ID');
            if (!$clientId) {
                throw new \Exception('GOOGLE_CLIENT_ID not set in .env');
            }

            $certPath = storage_path('app/cacert.pem');
            if (!file_exists($certPath)) {
                throw new \Exception('Certificate file not found at: ' . $certPath);
            }

            $guzzleClient = new \GuzzleHttp\Client([
                'verify' => $certPath,
            ]);

            $client = new \Google\Client(['client_id' => $clientId]);
            $client->setHttpClient($guzzleClient);

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
        */
    }
}
