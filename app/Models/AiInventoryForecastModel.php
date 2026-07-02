<?php

namespace App\Models;

use CodeIgniter\Model;

class AiInventoryForecastModel extends Model
{
    protected $table            = 'ai_inventory_forecasts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'medicine_id', 'forecast_date', 'forecast_period_start', 'forecast_period_end',
        'predicted_daily_usage', 'predicted_stockout_date', 'predicted_reorder_date',
        'current_stock', 'reorder_threshold', 'model_type', 'seasonality_factor',
        'confidence_interval_lower', 'confidence_interval_upper', 'accuracy_metrics',
    ];

    protected $useTimestamps = false;

    /**
     * Get the latest forecast for all medicines.
     */
    public function getLatestForecasts(): array
    {
        return $this->select('ai_inventory_forecasts.*, medicines.generic_name, medicines.brand_name, medicines.unit')
            ->join('medicines', 'medicines.id = ai_inventory_forecasts.medicine_id')
            ->where('forecast_date', $this->select('MAX(forecast_date)')->getCompiledSelect(false))
            ->findAll();
    }
}
