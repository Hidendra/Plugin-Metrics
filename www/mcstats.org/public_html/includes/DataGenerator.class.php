<?php
if (!defined('ROOT')) exit('For science.');

class DataGenerator
{

    /**
     * Generates custom chart data
     *
     * @static
     * @param $graph Graph
     * @param $columnID int
     * @return array
     */
    public static function generateCustomChartData($graph, $columnID, $hours = 372)
    {
        $_cacheid = 'CustomChart' . $columnID . $hours;

        // Check the cache
        if ($data = $graph->getPlugin()->cacheGet($_cacheid))
        {
            return $data;
        }

        $generatedData = array();

        // calculate the minimum
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
        $maximum = $baseEpoch;

        // Get all of the custom data points
        $dataPoints = $graph->getPlugin()->getTimelineCustom($columnID, $minimum, $maximum);

        // Add all of them to the array
        foreach ($dataPoints as $epoch => $dataPoint)
        {
            if ($dataPoint == 0)
            {
                continue;
            }

            $generatedData[] = array($epoch * 1000, $dataPoint);
        }

        // Cache it
        $graph->getPlugin()->cacheSet($_cacheid, $generatedData);

        return $generatedData;
    }

}