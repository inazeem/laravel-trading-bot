<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apiKeys = auth()->user()->apiKeys()->latest()->paginate(10);
        return view('api-keys.index', compact('apiKeys'));
    }

    public function create()
    {
        return view('api-keys.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exchange' => 'required|in:kucoin,binance',
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'passphrase' => 'nullable|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'in:read,trade,transfer',
            'is_active' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        // Validate that trade permission is included for trading bots
        if (!in_array('trade', $validated['permissions'])) {
            return back()->withInput()->with('error', 'Trading bots require "trade" permission.');
        }

        try {
            $apiKey = auth()->user()->apiKeys()->create($validated);
            
            return redirect()->route('api-keys.index')
                ->with('success', 'API key added successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating API key: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to add API key.');
        }
    }

    public function show(ApiKey $apiKey)
    {
        $this->authorize('view', $apiKey);
        
        $apiKey->load(['tradingBots' => function($query) {
            $query->latest()->limit(10);
        }]);
        
        return view('api-keys.show', compact('apiKey'));
    }

    public function edit(ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey);
        return view('api-keys.edit', compact('apiKey'));
    }

    public function update(Request $request, ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'passphrase' => 'nullable|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'in:read,trade,transfer',
            'is_active' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        // Validate that trade permission is included for trading bots
        if (!in_array('trade', $validated['permissions'])) {
            return back()->withInput()->with('error', 'Trading bots require "trade" permission.');
        }

        try {
            $apiKey->update($validated);
            
            return redirect()->route('api-keys.index')
                ->with('success', 'API key updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating API key: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update API key.');
        }
    }

    public function destroy(ApiKey $apiKey)
    {
        $this->authorize('delete', $apiKey);
        
        try {
            // Check if API key is being used by any trading bots
            if ($apiKey->tradingBots()->count() > 0) {
                return back()->with('error', 'Cannot delete API key that is being used by trading bots.');
            }
            
            $apiKey->delete();
            return redirect()->route('api-keys.index')
                ->with('success', 'API key deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting API key: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete API key.');
        }
    }

    public function toggleStatus(ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey);
        
        try {
            $apiKey->update(['is_active' => !$apiKey->is_active]);
            
            $status = $apiKey->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "API key {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('Error toggling API key status: ' . $e->getMessage());
            return back()->with('error', 'Failed to update API key status.');
        }
    }

    public function testConnection(ApiKey $apiKey)
    {
        $this->authorize('view', $apiKey);
        
        try {
            $exchangeService = new \App\Services\ExchangeService($apiKey);
            
            // Test connection by getting account balance
            $balance = $exchangeService->getAccountBalance();
            
            if (!empty($balance)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                    'balance' => $balance
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed - no balance data received'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('API key connection test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }
}
