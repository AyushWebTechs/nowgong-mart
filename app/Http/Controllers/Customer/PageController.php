<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\StoreSettings;
use Illuminate\View\View;

class PageController extends Controller
{
    public const PAGES = [
        'privacy' => 'Privacy policy',
        'terms' => 'Terms of service',
        'refunds' => 'Cancellation & refund policy',
        'contact' => 'Contact us',
    ];

    public function show(string $page, StoreSettings $settings): View
    {
        return view('pages.'.$page, ['title' => self::PAGES[$page], 'settings' => $settings]);
    }
}
