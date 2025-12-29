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
}
