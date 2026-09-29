<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Member;
use App\Services\MediaUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $query = CashTransaction::with(['member', 'proof', 'user'])->orderBy('created_at', 'desc');

        // Filter: Name
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->where(function ($q) use ($name) {
                $q->where('contributor_name', 'like', "%{$name}%")
                  ->orWhereHas('member', function ($mq) use ($name) {
                      $mq->where('full_name', 'like', "%{$name}%");
                  });
            });
        }

        // Filter: Account Type
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        // Filter: Amount
        if ($request->filled('amount')) {
            $query->where('amount', $request->input('amount'));
        }

        // Filter: Input Date
        if ($request->filled('date')) {
            $query->whereDate('transaction_date', $request->input('date'));
        }

        // Calculate total cash for filtered query
        $totalCash = (clone $query)->sum('amount');

        $transactions = $query->paginate($perPage)->withQueryString();

        // Get members for dropdown and shortcuts
        $members = Member::where('is_active', true)->orderBy('full_name', 'asc')->get();

        // Build member shortcuts map: member_id => ['account_type' => ..., 'amount' => ...]
        $shortcuts = [];
        foreach ($members as $member) {
            $lastTx = CashTransaction::where('member_id', $member->id)->latest('id')->first();
            if ($lastTx) {
                $shortcuts[$member->id] = [
                    'account_type' => $lastTx->account_type,
                    'amount' => (float) $lastTx->amount,
                ];
            }
        }

        // Distinct account types for filter dropdown
        $accountTypes = CashTransaction::whereNotNull('account_type')
            ->distinct()
            ->pluck('account_type');

        return view('pages.cash-management', [
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
            ],
        ]);
    }

    /**
     * Store a new cash contribution transaction with image proof.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'account_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $member = Member::findOrFail($validated['member_id']);

        // Upload image proof
        $media = $this->mediaService->storeImage(
            $request->file('proof'),
            'cash_proof',
            CashTransaction::class
        );

        $now = Carbon::now('Asia/Jakarta');

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
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $media->update(['fileable_id' => $transaction->id]);

        return redirect()->route('cash-management')->with('success', 'Cash transaction recorded successfully.');
    }

    /**
     * Return shortcut data for a selected member (account type & last amount).
     */
    public function shortcut(int $memberId): JsonResponse
    {
        $lastTx = CashTransaction::where('member_id', $memberId)->latest('id')->first();

        if (!$lastTx) {
            return response()->json([
                'has_shortcut' => false,
            ]);
        }

        return response()->json([
            'has_shortcut' => true,
            'account_type' => $lastTx->account_type,
            'amount' => (float) $lastTx->amount,
        ]);
    }
}
