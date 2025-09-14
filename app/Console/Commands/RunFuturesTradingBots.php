<?php

namespace App\Console\Commands;

use App\Models\FuturesTradingBot;
use App\Services\SimpleFuturesTradingBotService;
use Illuminate\Console\Command;

class RunFuturesTradingBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'futures:run {--bot= : Run specific bot by ID} {--all : Run all active bots}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run futures trading bots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botId = $this->option('bot');
        $runAll = $this->option('all');

        if ($botId) {
            $this->runSpecificBot($botId);
        } elseif ($runAll) {
            $this->runAllActiveBots();
        } else {
            $this->error('Please specify --bot=ID or --all option');
            return 1;
        }

        return 0;
    }

    /**
     * Run a specific bot by ID
     */
    private function runSpecificBot($botId)
    {
        $bot = FuturesTradingBot::find($botId);

        if (!$bot) {
            $this->error("Futures trading bot with ID {$botId} not found");
            return;
        }

        if (!$bot->is_active) {
            $this->warn("Futures trading bot '{$bot->name}' is not active");
            return;
        }

        $this->info("Running futures trading bot: {$bot->name}");
        
        try {
            $service = new SimpleFuturesTradingBotService($bot);
            $service->run();
            
            $this->info("✅ Futures trading bot '{$bot->name}' executed successfully");
        } catch (\Exception $e) {
            $this->error("❌ Error running futures trading bot '{$bot->name}': " . $e->getMessage());
        }
    }

    /**
     * Run all active bots
     */
    private function runAllActiveBots()
    {
        $bots = FuturesTradingBot::where('is_active', true)->get();

        if ($bots->isEmpty()) {
            $this->info("No active futures trading bots found");
            return;
        }

        $this->info("Found {$bots->count()} active futures trading bots");

        $bar = $this->output->createProgressBar($bots->count());
        $bar->start();

        foreach ($bots as $bot) {
            try {
                $service = new SimpleFuturesTradingBotService($bot);
                $service->run();
                
                $this->line(" ✅ {$bot->name}");
            } catch (\Exception $e) {
                $this->line(" ❌ {$bot->name}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Futures trading bots execution completed");
    }
}
