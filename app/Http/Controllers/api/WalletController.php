<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\PurchaseCreditsRequest;
use App\Http\Requests\Wallet\AddCustomCreditsRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\CreditPack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Get wallet balance and details
     */
    public function getWallet(Request $request)
    {
        $user = $request->user();
        
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'status' => 'ACTIVE'
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_id' => $wallet->wallet_id,
                'balance' => (float) $wallet->balance,
                'status' => $wallet->status,
                'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Get credit packs
     */
    public function getCreditPacks(Request $request)
    {
        $packs = CreditPack::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(function ($pack) {
                return [
                    'id' => $pack->id,
                    'name' => $pack->name,
                    'slug' => $pack->slug,
                    'credits' => $pack->credits,
                    'price' => (float) $pack->price,
                    'gst_percentage' => (float) $pack->gst_percentage,
                    'gst_amount' => (float) $pack->gst_amount,
                    'total_price' => (float) $pack->total_price,
                    'description' => $pack->description,
                    'validity' => $pack->validity,
                    'is_best_value' => $pack->is_best_value,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packs
        ]);
    }

    /**
     * Calculate custom credits
     */
    public function calculateCustomCredits(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $amount = $request->amount;
        $gstPercentage = 18; // Default GST
        $gstAmount = ($amount * $gstPercentage) / 100;
        $totalAmount = $amount + $gstAmount;
        
        // Calculate credits: ₹100 = 10 credits (example rate, adjust as needed)
        $credits = floor(($amount / 100) * 10);

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => (float) $amount,
                'gst_percentage' => $gstPercentage,
                'gst_amount' => (float) $gstAmount,
                'total_amount' => (float) $totalAmount,
                'credits' => $credits,
            ]
        ]);
    }

    /**
     * Purchase credits (from pack or custom)
     */
    public function purchaseCredits(PurchaseCreditsRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0,
                    'status' => 'ACTIVE'
                ]
            );

            $credits = 0;
            $packName = null;
            $amount = 0;
            $gstAmount = 0;
            $totalAmount = 0;

            if ($request->credit_pack_id) {
                // Purchase from pack
                $pack = CreditPack::findOrFail($request->credit_pack_id);
                $credits = $pack->credits;
                $packName = $pack->name;
                $amount = $pack->price;
                $gstAmount = $pack->gst_amount;
                $totalAmount = $pack->total_price;
            } else {
                // Custom amount
                $amount = $request->amount;
                $gstPercentage = $request->gst_percentage ?? 18;
                $gstAmount = ($amount * $gstPercentage) / 100;
                $totalAmount = $amount + $gstAmount;
                // Calculate credits: ₹100 = 10 credits
                $credits = floor(($amount / 100) * 10);
            }

            // Add credits to wallet
            $transaction = $wallet->addCredits(
                $credits,
                $packName ? "{$packName} - {$credits} Credits" : "Custom Credits - {$credits} Credits",
                'PURCHASE',
                null,
                null,
                [
                    'pack_id' => $request->credit_pack_id,
                    'amount' => $amount,
                    'gst_amount' => $gstAmount,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method ?? 'PENDING',
                    'payment_status' => 'PENDING', // Will update when payment gateway is integrated
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credits added successfully!',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'credits_added' => $credits,
                    'new_balance' => (float) $wallet->balance,
                    'amount_paid' => (float) $totalAmount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase credits error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase credits. Please try again.',
            ], 500);
        }
    }

    /**
     * Add custom credits (admin or system)
     */
    public function addCredits(AddCustomCreditsRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0,
                    'status' => 'ACTIVE'
                ]
            );

            $transaction = $wallet->addCredits(
                $request->credits,
                $request->description ?? 'Credits Added',
                $request->transaction_type ?? 'ADMIN_ADJUSTMENT',
                $request->reference_id,
                $request->reference_type,
                $request->metadata ?? []
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credits added successfully!',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'credits_added' => $request->credits,
                    'new_balance' => (float) $wallet->balance,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Add credits error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add credits. Please try again.',
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactions(Request $request)
    {
        $user = $request->user();
        
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return response()->json([
                'success' => true,
                'data' => [
                    'wallet_id' => null,
                    'balance' => 0,
                    'status' => 'ACTIVE',
                    'transactions' => [],
                    'total' => 0,
                ]
            ]);
        }

        $query = $wallet->transactions();

        // Filter by type (ADDED, DEDUCTED, or ALL)
        if ($request->filled('type') && $request->type !== 'ALL') {
            $query->where('type', $request->type);
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_id' => $wallet->wallet_id,
                'balance' => (float) $wallet->balance,
                'status' => $wallet->status,
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                        'type' => $transaction->type,
                        'amount' => (float) abs($transaction->amount),
                        'credits' => $transaction->type === 'ADDED' ? '+' . abs($transaction->amount) : '-' . abs($transaction->amount),
                        'balance_after' => (float) $transaction->balance_after,
                        'description' => $transaction->description,
                        'transaction_type' => $transaction->transaction_type,
                        'reference_id' => $transaction->reference_id,
                        'reference_type' => $transaction->reference_type,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'date' => $transaction->created_at->format('M d'),
                        'time' => $transaction->created_at->format('H:i'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Deduct credits (for posting requirements, etc.)
     */
    public function deductCredits(Request $request)
    {
        $request->validate([
            'credits' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_type' => 'nullable|string|in:REQUIREMENT_POSTED,DEAL_CLOSED,MACHINERY_INSPECTION,LISTING_FEE,PREMIUM_FEATURE,OTHER',
            'reference_id' => 'nullable|string',
            'reference_type' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please purchase credits first.',
                ], 404);
            }

            $transaction = $wallet->deductCredits(
                $request->credits,
                $request->description,
                $request->transaction_type ?? 'OTHER',
                $request->reference_id,
                $request->reference_type,
                $request->metadata ?? []
            );

            if (!$transaction) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits. Please purchase more credits.',
                    'data' => [
                        'current_balance' => (float) $wallet->balance,
                        'required' => $request->credits,
                    ]
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credits deducted successfully!',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'credits_deducted' => $request->credits,
                    'new_balance' => (float) $wallet->balance,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deduct credits error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to deduct credits. Please try again.',
            ], 500);
        }
    }
}
