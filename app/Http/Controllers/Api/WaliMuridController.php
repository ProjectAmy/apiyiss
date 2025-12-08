<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaliMurid;

class WaliMuridController extends Controller
{
    // ============================
    // 1. CHECK USER (GET)
    // URL: /api/walimurid/check?email=user@example.com
    // ============================
    public function check(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $wali = WaliMurid::where('email', $request->email)->first();

        if ($wali) {
            return response()->json([
                'message' => 'User ditemukan',
                'data' => $wali,
            ], 200);
        }

        return response()->json([
            'message' => 'User tidak ditemukan',
        ], 404);
    }

    // ============================
    // 2. REGISTER USER (POST)
    // URL: /api/walimurid/register
    // ============================
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:wali_murids,email',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $wali = WaliMurid::create($validated);

        return response()->json([
            'message' => 'User berhasil didaftarkan.',
            'data'    => $wali,
        ], 201);
    }
}
