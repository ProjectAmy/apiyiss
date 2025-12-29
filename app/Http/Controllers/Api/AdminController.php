<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;

class AdminController extends Controller
{
    public function stats()
    {
        $studentCount = Student::count();
        // Uses Spatie HasRoles scope
        $waliCount = User::role('Wali Murid')->count();

        return response()->json([
            'total_students' => $studentCount,
            'total_wali_murid' => $waliCount,
        ]);
    }
}
