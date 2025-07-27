<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Biller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillerController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $billers = Biller::active()->get();

        return $this->successResponse([
            'billers' => $billers,
        ]);
    }

    public function payBill(Request $request)
    {
        try {
            $request->validate([
                'biller_id' => 'required|exists:billers,id',
                'amount' => 'required|numeric|min:1',
                'account_number' => 'nullable|string|max:50',
            ]);

            $user = $request->user();
            $biller = Biller::findOrFail($request->biller_id);
            $amount = $request->amount;

            // Check if user has sufficient balance
            if ($user->balance < $amount) {
                return $this->errorResponse('Insufficient balance for this transaction');
            }

            $transaction = DB::transaction(function () use ($user, $biller, $amount, $request) {
                // Deduct from user balance
                $user->decrement('balance', $amount);

                // Create transaction record
                return Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => 'Bill payment to ' . $biller->name,
                    'metadata' => [
                        'biller_id' => $biller->id,
                        'biller_name' => $biller->name,
                        'biller_category' => $biller->category,
                        'account_number' => $request->account_number,
                    ],
                    'status' => 'completed',
                ]);
            });

            activity()
                ->causedBy($user)
                ->withProperties([
                    'amount' => $amount,
                    'biller' => $biller->name,
                    'account_number' => $request->account_number,
                ])
                ->log('User paid bill');

            return $this->successResponse([
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => 'bill_payment',
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->status,
                    'reference' => $transaction->reference,
                    'biller' => $biller->name,
                    'account_number' => $request->account_number,
                    'created_at' => $transaction->created_at->toISOString(),
                ],
                'new_balance' => (float) $user->fresh()->balance,
            ], 'Bill payment successful');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }
}
