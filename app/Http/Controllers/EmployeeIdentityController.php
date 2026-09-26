<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeIdentityController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
        ]);

        $employee = Employee::query()
            ->whereKey($validated['employee_id'])
            ->where('is_active', true)
            ->first();

        if (! $employee) {
            $message = 'Pegawai tidak ditemukan atau sudah tidak aktif.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('home')
                ->with('identity_error', $message);
        }

        $request->session()->put('employee_id', $employee->id);

        if ($request->expectsJson()) {
            return response()->json([
                'employee_id' => $employee->id,
            ]);
        }

        return redirect()->route('home');
    }
}
