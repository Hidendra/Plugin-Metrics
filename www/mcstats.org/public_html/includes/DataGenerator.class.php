<?php
if (!defined('ROOT')) exit('For science.');

class DataGenerator
{

    /**
     * Generates the chart data if it is not already cached
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

    /**
     * Generates the version chart data if it is not already cached
     * @param $version the version ID
     * @param plugin Plugin
     * @param hours
     * @return array
     */
    public static function generateVersionChartData($plugin, $versionID, $hours = 744)
    {
        if (($cache = $plugin->cacheGet('version-chart-data' . $versionID . $hours)) != NULL)
        {
            return $cache;
        }

        $generatedData = array();

        // calculate the minimum
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
        $maximum = $baseEpoch;

        // load the data from mysql
        $versions = $plugin->getTimelineVersion($versionID, $minimum, $maximum);

        // go through each and add to json
        $still_zero = TRUE;
        foreach ($versions as $epoch => $count)
        {
            if ($count > 0) {
                $still_zero = FALSE;
            }

            // Don't uglify the graph
            if ($count == 0) {
                continue;
            }

            $generatedData[] = array($epoch * 1000, $count);
        }

        // Ignore versions that have never been used
        if ($still_zero) {
            return array();
        }

        // Cache it!
        $plugin->cacheSet('version-chart-data' . $hours, $generatedData);

        return $generatedData;
    }

    /**
     * Generates the server chart data if it is not already cached
     * @return array
     */
    public static function generateServerChartData($plugin, $hours = 744)
    {
        if (($cache = $plugin->cacheGet('server-chart-data' . $hours)) != NULL)
        {
            return $cache;
        }

        $generatedData = array();

        // calculate the minimum
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
        $maximum = $baseEpoch;

        // load the data from mysql
        $servers = $plugin->getTimelineServers($minimum, $maximum);

        // go through each and add to json
        foreach ($servers as $epoch => $count)
        {
            $generatedData[] = array($epoch * 1000, $count);
        }

        // Cache it!
        $plugin->cacheSet('server-chart-data' . $hours, $generatedData);

        return $generatedData;
    }

    /**
     * Generates the player chart data if it is not already cached
     * @return array
     */
    public static function generatePlayerChartData($plugin, $hours = 744)
    {
        if (($cache = $plugin->cacheGet('player-chart-data-' . $hours)) != NULL)
        {
            return $cache;
        }

        $generatedData = array();

        // calculate the minimum
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
        $maximum = $baseEpoch;

        // load the data from mysql
        $players = $plugin->getTimelinePlayers($minimum, $maximum);

        // go through each and add to json
        foreach ($players as $epoch => $count)
        {
            $generatedData[] = array($epoch * 1000, $count);
        }

        // Cache it!
        $plugin->cacheSet('player-chart-data' . $hours, $generatedData);

        return $generatedData;
    }

    /**
     * Generates the country chart data if it is not already cached
     * @return array
     */
    public static function generateCountryChartData($plugin)
    {
        global $config;

        // Check the caches
        if (($cache = $plugin->cacheGet('country-chart-data')) != NULL)
        {
            return $cache;
        }

        // the array of country data
        $generatedData = array();

        // load all of hte available countries
        // this is mainly just for their names
        $countries = loadCountries();

        // Calculate the epoch to lookup
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-' . $config['graph']['interval'] . ' minutes', $baseEpoch);

        // load the data from mysql
        $servers = $plugin->getTimelineCountry($minimum);

        // go through each and add to json
        foreach ($servers as $epoch => $data)
        {
            // Sort the server counts
            asort(&$data);

            // Get the amount of servers we have
            $server_total = array_sum($data);

            // If it is bigger than MINIMUM_FOR_OTHERS, calculate what 'Others' would be
            $count = count($data);
            if ($count >= MINIMUM_FOR_OTHERS)
            {
                $others_total = 0;

                foreach ($data as $country => $amount)
                {
                    if ($count <= MINIMUM_FOR_OTHERS)
                    {
                        break;
                    }

                    $count--;
                    $others_total += $amount;
                    unset($data[$country]);
                }

                // Set the 'Others' stat
                $data['Others'] = $others_total;

                // Sort again
                arsort(&$data);
            }

            // Begin emitting unadulterated JSON
            foreach ($data as $country => $amount)
            {
                $key = ($country == 'Others') ? $country : $countries[$country];
                $generatedData[] = array($key, round(($amount / $server_total) * 100, 2));
            }

            // We only want one period
            break;
        }

        // Cache it!
        $plugin->cacheSet('country-chart-data', $generatedData);

        return $generatedData;
    }

}