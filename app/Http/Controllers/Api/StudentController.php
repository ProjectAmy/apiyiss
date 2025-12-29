<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::select('id', 'fullname')->get();
        return response()->json($students);
    }

    public function getByWali(Request $request)
    {
        $user = $request->user();

        if (!$user->walimuridProfile) {
            return response()->json(['message' => 'User is not a Wali Murid'], 403);
        }

        $students = $user->walimuridProfile->students;

        return response()->json($students);
    }

    public function adminList()
    {
        $students = Student::with('walimuridProfile')->get();
        return response()->json($students);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'walimurid_profile_id' => 'required|exists:walimurid_profiles,id',
            'nis' => 'required|string|max:50', // Assuming NIS is string
            'unit' => 'required|string|max:50',
            'grade' => 'required|string|max:50', // 'Kelas' mapped to 'grade'
        ]);

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Student created successfully',
            'data' => $student
        ], 201);
    }
}
