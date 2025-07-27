<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search in descriptions
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);
        
        $total = $query->count();
        $transactions = $query->limit($limit)->offset($offset)->get();

        // Transform transactions
        $transformedTransactions = $transactions->map(function ($transaction) {
            $data = [
                'id' => $transaction->id,
                'type' => $this->mapTransactionType($transaction->type),
                'amount' => (float) $transaction->amount,
                'description' => $transaction->description,
                'status' => $transaction->status,
                'reference' => $transaction->reference,
                'created_at' => $transaction->created_at->toISOString(),
            ];

            // Add type-specific data
            if ($transaction->metadata) {
                switch ($transaction->type) {
                    case 'transfer_out':
                        $data['recipient'] = $transaction->metadata['recipient_name'] ?? null;
                        $data['note'] = $transaction->metadata['note'] ?? null;
                        break;
                    case 'transfer_in':
                        $data['sender'] = $transaction->metadata['sender_name'] ?? null;
                        $data['note'] = $transaction->metadata['note'] ?? null;
                        break;
                    case 'payment':
                        $data['biller'] = $transaction->metadata['biller_name'] ?? null;
                        $data['account_number'] = $transaction->metadata['account_number'] ?? null;
                        break;
                }
            }

            return $data;
        });

        return $this->successResponse([
            'transactions' => $transformedTransactions,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $transaction = Transaction::where('user_id', $user->id)
            ->findOrFail($id);

        return $this->successResponse([
            'transaction' => $transaction,
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_topups' => Transaction::where('user_id', $user->id)
                ->where('type', 'topup')
                ->sum('amount'),
            'total_payments' => Transaction::where('user_id', $user->id)
                ->where('type', 'payment')
                ->sum('amount'),
            'total_transfers_out' => Transaction::where('user_id', $user->id)
                ->where('type', 'transfer_out')
                ->sum('amount'),
            'total_transfers_in' => Transaction::where('user_id', $user->id)
                ->where('type', 'transfer_in')
                ->sum('amount'),
            'transaction_count' => Transaction::where('user_id', $user->id)
                ->count(),
            'recent_transactions' => Transaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return $this->successResponse([
            'stats' => $stats,
        ]);
    }

    private function mapTransactionType($type)
    {
        $mapping = [
            'topup' => 'topup',
            'payment' => 'bill_payment',
            'transfer_out' => 'transfer_sent',
            'transfer_in' => 'transfer_received',
        ];

        return $mapping[$type] ?? $type;
    }
}
