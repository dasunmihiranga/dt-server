<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    use ApiResponse;

    public function testTopup(Request $request)
    {
        try {
            Log::info('Test topup started');
            
            $request->validate([
                'amount' => 'required|numeric|min:1|max:10000',
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;
            
            Log::info('User found: ' . $user->id);
            Log::info('Amount: ' . $amount);

            // Test creating transaction without DB transaction first
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amount,
                'description' => 'Test account top-up',
                'metadata' => ['payment_method' => 'test'],
                'status' => 'completed',
            ]);

            Log::info('Transaction created: ' . $transaction->id);

            // Update user balance
            $user->increment('balance', $amount);
            
            Log::info('User balance updated');

            return $this->successResponse([
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => 'topup',
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->status,
                    'reference' => $transaction->reference,
                    'created_at' => $transaction->created_at->toISOString(),
                ],
                'new_balance' => (float) $user->fresh()->balance,
            ], 'Test top-up successful');

        } catch (\Exception $e) {
            Log::error('Test topup failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return $this->errorResponse('Test top-up failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function testAuth(Request $request)
    {
        try {
            $user = $request->user();
            
            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'balance' => (float) $user->balance,
                ],
                'message' => 'Authentication working'
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Auth test failed: ' . $e->getMessage(), null, 500);
        }
    }
}
