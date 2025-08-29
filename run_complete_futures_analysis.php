<?php

echo "🚀 Complete Futures Trading Bot Analysis\n";
echo "========================================\n\n";

echo "Starting comprehensive analysis of your futures trading bot performance...\n\n";

// 1. Run database-based performance analysis
echo "📊 Phase 1: Database Performance Analysis\n";
echo "----------------------------------------\n";
include 'analyze_futures_trading_performance.php';

echo "\n\n";

// 2. Run Binance market analysis
echo "📈 Phase 2: Binance Market Analysis\n";
echo "-----------------------------------\n";
include 'binance_futures_market_analysis.php';

echo "\n\n";

echo "🎉 COMPLETE ANALYSIS FINISHED!\n";
echo "==============================\n\n";

echo "📋 Generated Reports:\n";
echo "• futures_trading_analysis_[timestamp].json - Database performance analysis\n";
echo "• binance_market_analysis_[timestamp].json - Market conditions analysis\n\n";

echo "🔍 Key Areas to Focus On:\n";
echo "1. Review HIGH priority recommendations from both analyses\n";
echo "2. Compare bot performance with market conditions\n";
echo "3. Adjust signal strength requirements based on findings\n";
echo "4. Optimize trading schedule based on volume patterns\n";
echo "5. Align position sizing with current market volatility\n\n";

echo "💡 Next Steps:\n";
echo "1. Implement recommendations gradually (test with small positions)\n";
echo "2. Monitor performance for 3-5 days after each change\n";
echo "3. Run this analysis weekly to track improvements\n";
echo "4. Focus on the highest impact recommendations first\n\n";

echo "✅ Analysis complete! Check the generated JSON reports for detailed findings.\n";
