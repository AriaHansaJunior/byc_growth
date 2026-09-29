<?php

namespace App\Http\Controllers;

use App\Services\GameStorageService;
use Illuminate\View\View;

class PageController extends Controller
{
    protected GameStorageService $storageService;

    public function __construct(GameStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Homepage (/) — Main BYC GROWTH Information Hub
     */
    public function home(): View
    {
        $finalScores = $this->storageService->getFinalScores();

        return view('welcome', [
            'finalScores' => $finalScores,
        ]);
    }

    /**
     * About Page (/about)
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Members Directory Foundation (/members)
     */
    public function members(): View
    {
        return view('pages.members');
    }

    /**
     * Cash Management Foundation (/cash-management)
     */
    public function cashManagement(): View
    {
        return view('pages.cash-management');
    }

    /**
     * Contact Us (/contact)
     */
    public function contact(): View
    {
        return view('pages.contact');
    }
}
