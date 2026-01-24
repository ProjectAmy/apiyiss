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

    public function getUnconnected()
    {
        $students = Student::whereNull('walimurid_profile_id')
            ->select('id', 'fullname')
            ->get();
        return response()->json($students);
    }

    public function connectWali(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'walimurid_profile_id' => 'required|exists:walimurid_profiles,id',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Optional: Check if already connected
        if ($student->walimurid_profile_id) {
            return response()->json(['message' => 'Student is already connected to a Guardian'], 400);
        }

        $student->walimurid_profile_id = $validated['walimurid_profile_id'];
        $student->save();

        return response()->json([
            'message' => 'Student connected successfully',
            'data' => $student
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'walimurid_profile_id' => 'nullable|exists:walimurid_profiles,id',
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
