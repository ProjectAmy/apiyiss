<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    public function callback(Request $request)
        {
            $payload = $request->all();

            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $signatureKey = $request->input('signature_key');

            $serverKey = config('midtrans.server_key');

            $generatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($generatedSignature !== $signatureKey) {
                Log::warning('Midtrans signature mismatch', $payload);
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            // ambil invoice
            $invoice = Invoice::where('order_id', $orderId)->first();
            if (!$invoice) {
                Log::warning('Invoice not found for order_id ' . $orderId, $payload);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            // tangani berbagai status midtrans
            $transactionStatus = $request->input('transaction_status'); // settlement, pending, deny, expire, cancel
            $paymentType = $request->input('payment_type');
            $transactionId = $request->input('transaction_id');

            // Simpan log/payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'transaction_id' => $transactionId,
                'payment_type' => $paymentType,
                'transaction_status' => $transactionStatus,
                'gross_amount' => (int)$grossAmount,
                'raw_response' => json_encode($payload),
            ]);

            // Ubah status invoice bila settlement
            if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
                $invoice->status = 'PAID';
                $invoice->save();
            } elseif ($transactionStatus === 'expire' || $transactionStatus === 'cancel' || $transactionStatus === 'deny') {
                $invoice->status = 'EXPIRED';
                $invoice->save();
            } else {
                // pending atau lainnya -> simpan saja
            }

            return response()->json(['message' => 'OK']);
        }

}
