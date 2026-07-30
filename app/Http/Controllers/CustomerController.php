<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where('user_id', Auth::id())
            ->where('nama_pelanggan', 'like', '%' . $query . '%')
            ->orderBy('nama_pelanggan')
            ->limit(10)
            ->get([
                'id',
                'nama_pelanggan',
                'no_telp',
            ]);

        return response()->json($customers);
    }
}
