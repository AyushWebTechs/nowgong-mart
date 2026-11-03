<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Every SMS code sent: who asked, from where, and whether it was verified. */
class LoginHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = preg_replace('/\D/', '', (string) $request->query('q'));

        $codes = OtpCode::with('user')
            ->when($search !== '', fn ($q) => $q->where('phone', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.login-history', [
            'codes' => $codes,
            'today' => [
                'sent' => OtpCode::whereDate('created_at', today())->count(),
                'verified' => OtpCode::whereDate('created_at', today())->whereNotNull('verified_at')->count(),
            ],
        ]);
    }
}
