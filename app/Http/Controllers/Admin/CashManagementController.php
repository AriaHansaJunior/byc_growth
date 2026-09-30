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

        $query = CashTransaction::with(['member', 'proof', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

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

        // Filter: Input Date (safe Carbon parse checking server timestamp and date)
        if ($request->filled('date')) {
            $date = $request->input('date');
            try {
                $parsedDate = Carbon::parse($date)->toDateString();
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

        $transactions = $query->paginate($perPage)->withQueryString();

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

        $viewName = $request->is('admin/*') ? 'admin.cash-management' : 'pages.cash-management';

        return view($viewName, [
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
     * Dedicated Admin Portal Cash Management endpoint.
     */
    public function adminIndex(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Store a new cash contribution transaction with image proof.
     */
    public function store(Request $request): RedirectResponse
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

            DB::commit();

            return redirect()->route('cash-management')->with('success', 'Cash transaction recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($media) {
                $this->mediaService->deleteMediaFile($media);
            }

            Log::error('Failed to record cash transaction: ' . $e->getMessage(), ['exception' => $e]);

            return back()->withInput()->withErrors(['error' => 'Failed to record cash transaction. Please try again.']);
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
}
