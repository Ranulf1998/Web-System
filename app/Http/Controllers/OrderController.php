<?php
// filepath: c:\xampp\htdocs\cofeesaas\brewcloud\app\Http\Controllers\OrderController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view reports')->only(['index', 'show']);
    }

    public function index() {}
    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}