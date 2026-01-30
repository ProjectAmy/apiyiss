<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Invoice;

class InvoiceController extends Controller
{


    public function index(Request $request)
    {
        $user = $request->user();

        // Jika user adalah admin/staff (bukan walimurid), mungkin ingin melihat semua?
        // Untuk sekarang asumsi ini endpoint khusus walimurid atau handling role user

        if ($user->walimuridProfile) {
            // Ambil semua student milik user ini
            $studentIds = $user->walimuridProfile->students->pluck('id');

            // Ambil invoice yang student_id-nya ada di list student user ini
            $invoices = Invoice::whereIn('student_id', $studentIds)->get();
        } else {
            // Fallback, mungkin user belum punya profile atau bukan walimurid
            // Bisa return kosong atau error, disini kita return kosong saja
            $invoices = [];
        }

        return response()->json(['data' => $invoices]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|numeric',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
        ]);

        // Auto generate order_id: INV-{timestamp}-{random}
        $orderId = 'INV-' . time() . '-' . rand(100, 999);

        try {
            $invoice = Invoice::create([
                'order_id' => $orderId,
                'student_id' => $request->student_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'status' => 'UNPAID'
            ]);

            return response()->json([
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create invoice', 'error' => $e->getMessage()], 500);
        }
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
        ]);

        $students = \App\Models\Student::all();
        $createdCount = 0;

        // Gunakan transaction biar aman kalau ada error di tengah jalan
        \Illuminate\Support\Facades\DB::transaction(function () use ($students, $request, &$createdCount) {
            foreach ($students as $student) {
                // Generate unique order_id per invoice
                // Format: INV-{timestamp}-{student_id}-{random}
                $orderId = 'INV-' . time() . '-' . $student->id . '-' . rand(100, 999);

                Invoice::create([
                    'order_id' => $orderId,
                    'student_id' => $student->id,
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'status' => 'UNPAID'
                ]);
                $createdCount++;
            }
        });

        return response()->json([
            'message' => "Successfully created invoice for {$createdCount} students",
            'count' => $createdCount
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        return response()->json(['data' => $invoice]);
    }

    public function generateSnapToken($id)
    {
        $invoice = Invoice::with('student.walimuridProfile.user')->findOrFail($id);
        $student = $invoice->student;
        $profile = $student ? $student->walimuridProfile : null;
        $user = $profile ? $profile->user : null;

        // set midtrans config
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production') === true;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Fix: CURL Error: error setting certificate file
        Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            // Fix: Undefined array key 10023 (CURLOPT_HTTPHEADER)
            CURLOPT_HTTPHEADER => [],
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $invoice->order_id,
                'gross_amount' => (int) $invoice->amount,
            ],
            'item_details' => [
                [
                    'id' => $invoice->id,
                    'price' => (int) $invoice->amount,
                    'quantity' => 1,
                    'name' => $invoice->description . ' - ' . ($student->fullname ?? 'Siswa'),
                ]
            ],
            'customer_details' => [
                'first_name' => $profile->fullname ?? ($user->name ?? 'Wali Murid'),
                'email' => $user->email ?? '',
                'phone' => $profile->phone ?? '',
                'billing_address' => [
                    'first_name' => $profile->fullname ?? ($user->name ?? 'Wali Murid'),
                    'email' => $user->email ?? '',
                    'phone' => $profile->phone ?? '',
                    'address' => $profile->address ?? '',
                ],
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $invoice->snap_token = $snapToken;
            $invoice->status = 'PENDING';
            $invoice->save();

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function adminIndex()
    {
        $invoices = Invoice::with(['student.walimuridProfile'])->get();
        return response()->json(['data' => $invoices]);
    }

}
