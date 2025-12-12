<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Invoice;

class InvoiceController extends Controller
{


    public function index()
    {
        $invoices = Invoice::all();
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
        $invoice = Invoice::findOrFail($id);

        // Jika sudah ada snap_token dan belum expire, boleh return yang sudah ada
        if ($invoice->snap_token) {
            return response()->json(['snap_token' => $invoice->snap_token]);
        }

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
                'gross_amount' => $invoice->amount,
            ],
            'customer_details' => [
                'first_name' => 'Wali', // isi sesuai data
                'email' => 'parent@example.com'
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

}
