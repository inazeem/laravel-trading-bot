<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiKey;
use App\Services\ExchangeService;

class SyncAssetsFromExchange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:sync {--exchange= : Specific exchange to sync from} {--user= : Specific user ID to sync for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync trading pairs from exchange APIs to local assets table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exchange = $this->option('exchange');
        $userId = $this->option('user');

        $query = ApiKey::where('is_active', true);
        
        if ($exchange) {
            $query->where('exchange', $exchange);
        }
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $apiKeys = $query->get();

        if ($apiKeys->isEmpty()) {
            $this->error('No active API keys found.');
            return 1;
        }

        $this->info("Found {$apiKeys->count()} active API key(s) to sync from.");

        $totalSynced = 0;
        $totalUpdated = 0;

        foreach ($apiKeys as $apiKey) {
            $this->info("Syncing from {$apiKey->exchange} for user {$apiKey->user->name}...");

            try {
                $exchangeService = new ExchangeService($apiKey);
                
                // Sync assets
                $syncedCount = $exchangeService->syncAssets();
                $totalSynced += $syncedCount;
                
                $this->info("Synced {$syncedCount} assets from {$apiKey->exchange}");
                
                // Update prices
                $updatedCount = $exchangeService->updatePrices();
                $totalUpdated += $updatedCount;
                
                $this->info("Updated prices for {$updatedCount} assets from {$apiKey->exchange}");

            } catch (\Exception $e) {
                $this->error("Failed to sync from {$apiKey->exchange}: " . $e->getMessage());
            }
        }

        $this->info("Sync completed! Total synced: {$totalSynced}, Total updated: {$totalUpdated}");
        return 0;
    }
}
