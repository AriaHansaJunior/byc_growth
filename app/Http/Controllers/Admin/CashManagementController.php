<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\Member;
use App\Services\MediaUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;


class CashManagementController extends Controller
{
    protected MediaUploadService $mediaService;

    public function __construct(MediaUploadService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display the admin Cash Management ledger, filters, and transaction form.
     */
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->input('per_page'), [5, 10, 25, 50], true)
            ? (int) $request->input('per_page')
            : 10;

        $sort = $request->input('sort', 'date_desc');
        $query = CashTransaction::with(['member', 'proof', 'user']);

        switch ($sort) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('amount', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('amount', 'asc')->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('contributor_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('contributor_name', 'desc');
                break;
            case 'date_desc':
            default:
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
                break;
        }

        // Filter: Name
        if ($request->filled('name')) {
            $name = trim($request->input('name'));
            $escapedName = addcslashes($name, '%_\\');
            $query->where(function ($q) use ($escapedName) {
                $q->where('contributor_name', 'like', "%{$escapedName}%")
                  ->orWhereHas('member', function ($mq) use ($escapedName) {
                      $mq->where('full_name', 'like', "%{$escapedName}%");
                  });
            });
        }

        // Filter: Account Type
        if ($request->filled('account_type')) {
            $query->where('account_type', trim($request->input('account_type')));
        }

        // Filter: Amount (safe numeric validation)
        if ($request->filled('amount')) {
            $amount = $request->input('amount');
            if (is_numeric($amount) && (float) $amount > 0) {
                $query->where('amount', (float) $amount);
            }
        }

        // Filter: Input Date (safe Carbon parse checking server timestamp and date; max today)
        if ($request->filled('date')) {
            $date = $request->input('date');
            try {
                $parsedDate = Carbon::parse($date)->toDateString();
                $todayStr = Carbon::now('Asia/Jakarta')->toDateString();
                if ($parsedDate > $todayStr) {
                    $parsedDate = $todayStr;
                }
                $query->where(function ($dq) use ($parsedDate) {
                    $dq->whereDate('created_at', $parsedDate)
                       ->orWhereDate('transaction_date', $parsedDate);
                });
            } catch (\Throwable $e) {
                // Invalid date format ignored safely
            }
        }

        // Calculate total cash for filtered query across all matching records (not just current page)
        $totalCash = (clone $query)->sum('amount');

        $transactions = $query->paginate($perPage)->appends($request->query());

        // Get members for dropdown and shortcuts
        $members = Member::where('is_active', true)->orderBy('full_name', 'asc')->get();

        // Build member shortcuts map: member_id => ['account_type' => ..., 'amount' => ...]
        $shortcuts = [];
        foreach ($members as $member) {
            $lastTx = CashTransaction::where('member_id', $member->id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastTx) {
                $shortcuts[$member->id] = [
                    'account_type' => $lastTx->account_type,
                    'amount' => (float) $lastTx->amount,
                ];
            }
        }

        // Distinct account types for filter dropdown
        $accountTypes = CashTransaction::whereNotNull('account_type')
            ->where('account_type', '!=', '')
            ->distinct()
            ->orderBy('account_type', 'asc')
            ->pluck('account_type');

        return view('admin.cash-management', [
            'transactions' => $transactions,
            'totalCash' => $totalCash,
            'members' => $members,
            'shortcuts' => $shortcuts,
            'accountTypes' => $accountTypes,
            'currentPerPage' => $perPage,
            'filters' => [
                'name' => $request->input('name', ''),
                'account_type' => $request->input('account_type', ''),
                'amount' => $request->input('amount', ''),
                'date' => $request->input('date', ''),
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Store a new cash contribution transaction with image proof.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_type' => 'required|string|max:100',
            'amount' => 'required|numeric|gt:0',
            'proof' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $media = null;

        try {
            DB::beginTransaction();

            // Upload image proof
            $media = $this->mediaService->storeImage(
                $request->file('proof'),
                'cash_proof',
                CashTransaction::class
            );

            // Server-side timestamp authority (Asia/Jakarta timezone)
            $now = Carbon::now('Asia/Jakarta');
            $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
            $adminEmail = $adminUser ? $adminUser->email : 'admin@bycgrowth.org';

            $transaction = CashTransaction::create([
                'user_id' => Auth::id(),
                'member_id' => $member->id,
                'contributor_name' => $member->full_name,
                'amount' => $validated['amount'],
                'account_type' => $validated['account_type'],
                'type' => 'inflow',
                'description' => 'Monthly Cash Contribution - ' . $member->full_name,
                'proof_file_id' => $media->id,
                'transaction_date' => $now->toDateString(),
                'last_action_by' => $adminEmail,
                'last_action_type' => 'created',
                'last_action_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $media->update(['fileable_id' => $transaction->id]);

            AuditLog::record($adminUser, 'created', 'cash_transaction', $transaction->id, "Recorded transaction Rp " . number_format($transaction->amount, 0, ',', '.') . " for '{$transaction->contributor_name}'");

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash transaction recorded successfully.',
                    'transaction' => $transaction,
                ], 201);
            }

            return redirect()->route('cash-management')->with('success', 'Cash transaction recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($media) {
                $this->mediaService->deleteMediaFile($media);
            }

            Log::error('Failed to record cash transaction: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record cash transaction.',
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to record cash transaction. Please try again.']);
        }
    }

    /**
     * Get transaction details (JSON).
     */
    public function show(int $id): JsonResponse
    {
        $transaction = CashTransaction::with(['member', 'proof', 'user'])->findOrFail($id);

        return response()->json([
            'id' => $transaction->id,
            'member_id' => $transaction->member_id,
            'contributor_name' => $transaction->member ? $transaction->member->full_name : $transaction->contributor_name,
            'account_type' => $transaction->account_type,
            'amount' => (float) $transaction->amount,
            'proof_url' => $transaction->proof_file_url,
            'has_proof' => (bool) $transaction->proof_file_id,
            'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : null,
            'created_at' => $transaction->created_at ? $transaction->created_at->format('M j, Y H:i:s') : null,
        ]);
    }

    /**
     * Update an existing cash transaction.
     */
    public function update(Request $request, int $id)
    {
        $transaction = CashTransaction::with('proof')->findOrFail($id);

        $validated = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'contributor_name' => 'nullable|string|max:255',
            'account_type' => 'required|string|max:100',
            'amount' => 'required|numeric|gt:0',
            'proof' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $newMedia = null;
        $oldMedia = $transaction->proof;

        try {
            DB::beginTransaction();

            if ($request->hasFile('proof')) {
                $newMedia = $this->mediaService->storeImage(
                    $request->file('proof'),
                    'cash_proof',
                    CashTransaction::class,
                    $transaction->id
                );
                $transaction->proof_file_id = $newMedia->id;
            }

            if (!empty($validated['member_id'])) {
                $member = Member::findOrFail($validated['member_id']);
                $transaction->member_id = $member->id;
                $transaction->contributor_name = $member->full_name;
            } elseif (!empty($validated['contributor_name'])) {
                $transaction->member_id = null;
                $transaction->contributor_name = $validated['contributor_name'];
            }

            $transaction->account_type = $validated['account_type'];
            $transaction->amount = $validated['amount'];
            $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
            $adminEmail = $adminUser ? $adminUser->email : 'admin@bycgrowth.org';
            $transaction->last_action_by = $adminEmail;
            $transaction->last_action_type = 'edited';
            $transaction->last_action_at = Carbon::now('Asia/Jakarta');
            $transaction->save();

            AuditLog::record($adminUser, 'edited', 'cash_transaction', $transaction->id, "Updated transaction #{$transaction->id} for '{$transaction->contributor_name}'");

            // Safely delete old proof after successful update
            if ($newMedia && $oldMedia) {
                $this->mediaService->deleteMediaFile($oldMedia);
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash transaction updated successfully.',
                    'transaction' => $transaction->fresh(['member', 'proof']),
                ]);
            }

            return back()->with('success', 'Cash transaction updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newMedia) {
                $this->mediaService->deleteMediaFile($newMedia);
            }

            Log::error('Failed to update cash transaction: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update cash transaction.',
                    'error' => 'Failed to update cash transaction.',
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to update cash transaction. Please try again.']);
        }
    }

    /**
     * Delete an existing cash transaction.
     */
    public function destroy(Request $request, int $id)
    {
        $transaction = CashTransaction::with('proof')->findOrFail($id);
        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $txId = $transaction->id;
        $contributor = $transaction->contributor_name;

        try {
            DB::beginTransaction();

            if ($transaction->proof) {
                $this->mediaService->deleteMediaFile($transaction->proof);
            }

            $transaction->delete();

            AuditLog::record($adminUser, 'deleted', 'cash_transaction', $txId, "Deleted transaction #{$txId} for '{$contributor}'");

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash transaction deleted successfully.',
                ]);
            }

            return back()->with('success', 'Cash transaction deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete cash transaction: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete cash transaction.',
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to delete cash transaction.']);
        }
    }

    /**
     * Return shortcut data for a selected member (account type & last amount).
     */
    public function shortcut(int $memberId): JsonResponse
    {
        $member = Member::find($memberId);
        if (!$member) {
            return response()->json([
                'has_shortcut' => false,
                'message' => 'Member not found.',
            ], 404);
        }

        $lastTx = CashTransaction::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastTx) {
            return response()->json([
                'has_shortcut' => false,
                'account_type' => null,
                'amount' => null,
            ]);
        }

        return response()->json([
            'has_shortcut' => true,
            'account_type' => $lastTx->account_type,
            'amount' => (float) $lastTx->amount,
        ]);
    }

    /**
     * Remove multiple cash transactions and their proof files in bulk.
     */
    public function batchDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:cash_transactions,id',
        ]);

        $ids = $validated['ids'];

        try {
            DB::beginTransaction();

            $transactions = CashTransaction::with('proof')->whereIn('id', $ids)->get();
            $count = $transactions->count();

            foreach ($transactions as $transaction) {
                if ($transaction->proof) {
                    $this->mediaService->deleteMediaFile($transaction->proof);
                }
                $transaction->delete();
            }

            $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
            AuditLog::record($adminUser, 'batch_deleted', 'cash_transaction', null, "Batch deleted {$count} transactions");

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Selected {$count} cash transactions deleted successfully.",
                ]);
            }

            return redirect()->route('admin.cash-management')
                ->with('success', "Selected {$count} " . ($count === 1 ? 'cash transaction has' : 'cash transactions have') . ' been deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Batch delete cash transactions failed: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete selected cash transactions.',
                ], 500);
            }

            return back()->with('error', 'Failed to delete selected cash transactions.');
        }
    }
}
