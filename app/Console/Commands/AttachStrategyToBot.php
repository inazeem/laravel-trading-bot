<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StrategyFactory;
use App\Models\TradingBot;
use App\Models\FuturesTradingBot;
use App\Models\TradingStrategy;

class AttachStrategyToBot extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'trading:attach-strategy {bot_id} {strategy_id} {--parameters=} {--priority=1}';

    /**
     * The console command description.
     */
    protected $description = 'Attach a trading strategy to a bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botId = $this->argument('bot_id');
        $strategyId = $this->argument('strategy_id');
        $priority = (int) $this->option('priority');
        
        // Find the bot
        $bot = $this->findBot($botId);
        if (!$bot) {
            $this->error("❌ Bot with ID {$botId} not found.");
            return 1;
        }
        
        // Find the strategy
        $strategy = TradingStrategy::find($strategyId);
        if (!$strategy) {
            $this->error("❌ Strategy with ID {$strategyId} not found.");
            return 1;
        }
        
        // Check compatibility
        if (!$this->checkCompatibility($bot, $strategy)) {
            $this->error("❌ Strategy '{$strategy->name}' is not compatible with this bot.");
            return 1;
        }
        
        // Parse parameters
        $parameters = $this->parseParameters($strategy);
        
        try {
            // Attach strategy to bot
            $botStrategy = StrategyFactory::attachStrategyToBot($bot, $strategyId, $parameters, $priority);
            
            $this->info("✅ Successfully attached strategy '{$strategy->name}' to bot '{$bot->name}'!");
            $this->displayAttachmentDetails($bot, $strategy, $parameters, $priority);
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to attach strategy: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Find bot by ID (check both spot and futures bots)
     */
    private function findBot($botId)
    {
        $bot = TradingBot::find($botId);
        if ($bot) {
            return $bot;
        }
        
        return FuturesTradingBot::find($botId);
    }
    
    /**
     * Check if strategy is compatible with bot
     */
    private function checkCompatibility($bot, $strategy): bool
    {
        $botType = $bot instanceof FuturesTradingBot ? 'futures' : 'spot';
        
        // Check market type compatibility
        if ($strategy->market_type !== 'both' && $strategy->market_type !== $botType) {
            return false;
        }
        
        // Check timeframe compatibility
        $botTimeframes = $bot->timeframes ?? ['1h', '4h', '1d'];
        $strategyTimeframes = $strategy->supported_timeframes ?? [];
        
        if (!empty($strategyTimeframes)) {
            $compatibleTimeframes = array_intersect($botTimeframes, $strategyTimeframes);
            if (empty($compatibleTimeframes)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Parse parameters from command line or prompt user
     */
    private function parseParameters($strategy): array
    {
        $parameters = [];
        $parametersOption = $this->option('parameters');
        
        if ($parametersOption) {
            // Parse JSON parameters
            $decoded = json_decode($parametersOption, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parameters = $decoded;
            }
        } else {
            // Interactive parameter input
            $this->info("\n📝 Configure strategy parameters:");
            
            foreach ($strategy->parameters as $param) {
                $value = $this->askParameter($param);
                if ($value !== null) {
                    $parameters[$param->parameter_name] = $value;
                }
            }
        }
        
        return $parameters;
    }
    
    /**
     * Ask for parameter value
     */
    private function askParameter($param)
    {
        $default = $param->default_value;
        $description = $param->description ? " ({$param->description})" : '';
        
        switch ($param->parameter_type) {
            case 'boolean':
                return $this->confirm("{$param->parameter_name}{$description}", $default);
                
            case 'integer':
                return $this->ask("{$param->parameter_name}{$description}", $default);
                
            case 'float':
                return (float) $this->ask("{$param->parameter_name}{$description}", $default);
                
            case 'select':
                return $this->choice("{$param->parameter_name}{$description}", $param->options, $default);
                
            default:
                return $this->ask("{$param->parameter_name}{$description}", $default);
        }
    }
    
    /**
     * Display attachment details
     */
    private function displayAttachmentDetails($bot, $strategy, $parameters, $priority)
    {
        $this->line("\n📋 Attachment Details:");
        $this->line("─────────────────────────────────────────────────────────────");
        $this->line("Bot: {$bot->name} (ID: {$bot->id})");
        $this->line("Strategy: {$strategy->name} (ID: {$strategy->id})");
        $this->line("Priority: {$priority}");
        
        if (!empty($parameters)) {
            $this->line("Parameters:");
            foreach ($parameters as $key => $value) {
                $this->line("  • {$key}: " . (is_array($value) ? json_encode($value) : $value));
            }
        }
        
        $this->line("\n💡 The bot will now use this strategy for trading decisions!");
    }
}
