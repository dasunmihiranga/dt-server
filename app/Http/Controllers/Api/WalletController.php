<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    use ApiResponse;

    public function topup(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1|max:10000',
                'payment_method' => 'nullable|string',
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;

            $transaction = DB::transaction(function () use ($user, $amount, $request) {
                // Update user balance
                $user->increment('balance', $amount);

                // Create transaction record
                return Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'topup',
                    'amount' => $amount,
                    'description' => 'Account top-up',
                    'metadata' => [
                        'payment_method' => $request->payment_method ?? 'credit_card',
                    ],
                    'status' => 'completed',
                ]);
            });

            // Log activity after successful transaction
            try {
                activity()
                    ->causedBy($user)
                    ->withProperties(['amount' => $amount])
                    ->log('User topped up account');
            } catch (\Exception $e) {
                \Log::warning('Activity log failed: ' . $e->getMessage());
            }

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
            ], 'Top-up successful');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            \Log::error('Topup failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->errorResponse('Top-up failed. Please try again.', null, 500);
        }
    }

    public function transfer(Request $request)
    {
        try {
            $request->validate([
                'recipient_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:1',
                'note' => 'nullable|string|max:255',
            ]);

            $sender = $request->user();
            $recipient = User::findOrFail($request->recipient_id);
            $amount = $request->amount;

            // Check if sender has sufficient balance
            if ($sender->balance < $amount) {
                return $this->errorResponse('Insufficient balance for this transaction');
            }

            // Check if trying to transfer to self
            if ($sender->id === $recipient->id) {
                return $this->errorResponse('Cannot transfer to yourself');
            }

            $transaction = DB::transaction(function () use ($sender, $recipient, $amount, $request) {
                // Deduct from sender
                $sender->decrement('balance', $amount);

                // Add to recipient
                $recipient->increment('balance', $amount);

                // Create outgoing transaction for sender
                $senderTransaction = Transaction::create([
                    'user_id' => $sender->id,
                    'type' => 'transfer_out',
                    'amount' => $amount,
                    'description' => 'Transfer to ' . $recipient->name,
                    'metadata' => [
                        'recipient_id' => $recipient->id,
                        'recipient_name' => $recipient->name,
                        'recipient_email' => $recipient->email,
                        'note' => $request->note,
                    ],
                    'status' => 'completed',
                ]);

                // Create incoming transaction for recipient
                Transaction::create([
                    'user_id' => $recipient->id,
                    'type' => 'transfer_in',
                    'amount' => $amount,
                    'description' => 'Transfer from ' . $sender->name,
                    'metadata' => [
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'sender_email' => $sender->email,
                        'note' => $request->note,
                    ],
                    'status' => 'completed',
                ]);

                return $senderTransaction;
            });

            activity()
                ->causedBy($sender)
                ->withProperties([
                    'amount' => $amount,
                    'recipient' => $recipient->email,
                ])
                ->log('User transferred funds');

            activity()
                ->causedBy($recipient)
                ->withProperties([
                    'amount' => $amount,
                    'sender' => $sender->email,
                ])
                ->log('User received funds');

            return $this->successResponse([
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => 'transfer_sent',
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->status,
                    'reference' => $transaction->reference,
                    'recipient' => $recipient->name,
                    'note' => $request->note,
                    'created_at' => $transaction->created_at->toISOString(),
                ],
                'new_balance' => (float) $sender->fresh()->balance,
            ], 'Transfer successful');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }

    public function balance(Request $request)
    {
        return $this->successResponse([
            'balance' => (float) $request->user()->balance,
        ]);
    }
}
