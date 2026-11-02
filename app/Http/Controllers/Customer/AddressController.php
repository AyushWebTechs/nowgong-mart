<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.addresses', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->addresses()->create([...$request->validated(), 'is_default' => ! $user->addresses()->exists()]);

        return back()->with('success', 'Address saved.');
    }

    public function makeDefault(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', 'Default address updated.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }
}
