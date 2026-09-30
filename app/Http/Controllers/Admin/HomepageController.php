<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomepageController extends Controller
{
    /**
     * Display the Admin Homepage Management portal.
     * Manages hero slideshow, community highlights, and showcase data.
     */
    public function index(): View
    {
        // Showcase & Slideshow placeholder items for S1 architecture
        $slides = [
            [
                'id' => 1,
                'title' => 'Growing in Faith & Fellowship',
                'caption' => 'A vibrant youth fellowship rooted in faith, love, and spiritual unity.',
                'image_url' => asset('assets/images/hero-1.jpg'),
                'order' => 1,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'title' => 'Sunday Service & Worship',
                'caption' => 'Connecting hearts through passionate worship, prayer, and God\'s Word.',
                'image_url' => asset('assets/images/hero-2.jpg'),
                'order' => 2,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'title' => 'Community Outreach & Service',
                'caption' => 'Sharing Christ\'s love through active service and genuine community care.',
                'image_url' => asset('assets/images/hero-3.jpg'),
                'order' => 3,
                'is_active' => true,
            ],
        ];

        return view('admin.homepage', [
            'slides' => $slides,
        ]);
    }
}
