<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class VoyagerOrderController extends Controller
{
    public function index()
    {
        // Fetch orders where status is not 'pending'
        $items = Order::where('request_status', '!=', 'pending')->get();

        return view('orders.index', compact('items'));
    }
}
