<?php

namespace App\Http\Controllers;

use App\Models\Spin;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $page = request("page") ?? 1;

        $spins = Spin::orderBy("created_at", "desc")->paginate(10);

        if ($page > $spins->lastPage()) {
            return redirect()->route("dashboard", [
                "page" => $spins->lastPage(),
            ]);
        }

        return Inertia::render("Dashboard", [
            "spins" => $spins,
        ]);
    }
}
