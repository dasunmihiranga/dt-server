<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        $user = $request->user();
        
        // Get current month and previous month
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        
        // Calculate totals
        $totalIncome = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['topup', 'transfer_in'])
            ->sum('amount');
            
        $totalExpenses = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['payment', 'transfer_out'])
            ->sum('amount');

        // Current month spending
        $currentMonthSpending = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['payment', 'transfer_out'])
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');

        // Previous month spending
        $previousMonthSpending = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['payment', 'transfer_out'])
            ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('amount');

        // Transaction counts
        $recentTransactionsCount = Transaction::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $pendingTransactionsCount = Transaction::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Transaction breakdown
        $topupsData = Transaction::where('user_id', $user->id)
            ->where('type', 'topup')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->first();

        $transfersSentData = Transaction::where('user_id', $user->id)
            ->where('type', 'transfer_out')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->first();

        $transfersReceivedData = Transaction::where('user_id', $user->id)
            ->where('type', 'transfer_in')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->first();

        $billsData = Transaction::where('user_id', $user->id)
            ->where('type', 'payment')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->first();

        $stats = [
            'current_balance' => (float) $user->balance,
            'total_income' => (float) $totalIncome,
            'total_expenses' => (float) $totalExpenses,
            'recent_transactions_count' => $recentTransactionsCount,
            'pending_transactions_count' => $pendingTransactionsCount,
            'monthly_spending' => [
                'current_month' => (float) $currentMonthSpending,
                'previous_month' => (float) $previousMonthSpending,
            ],
            'transaction_summary' => [
                'topups' => [
                    'count' => $topupsData->count ?? 0,
                    'total_amount' => (float) ($topupsData->total_amount ?? 0),
                ],
                'transfers' => [
                    'sent' => [
                        'count' => $transfersSentData->count ?? 0,
                        'total_amount' => (float) ($transfersSentData->total_amount ?? 0),
                    ],
                    'received' => [
                        'count' => $transfersReceivedData->count ?? 0,
                        'total_amount' => (float) ($transfersReceivedData->total_amount ?? 0),
                    ],
                ],
                'bills' => [
                    'count' => $billsData->count ?? 0,
                    'total_amount' => (float) ($billsData->total_amount ?? 0),
                ],
            ],
        ];

        return $this->successResponse([
            'stats' => $stats,
        ]);
    }
}
