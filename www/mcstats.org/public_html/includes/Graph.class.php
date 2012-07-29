<?php
if (!defined('ROOT')) exit('For science.');

/**
 * What type of graph to generate
 * Abstract is to prevent instantiation or inheritance
 */
abstract class GraphType
{

    /**
     * A line graph
     */
    const Line = 0;

    /**
     * An area graph
     */
    const Area = 1;

    /**
     * Column graph
     */
    const Column = 2;

    /**
     * A pie graph
     */
    const Pie = 3;

}

/**
 * The graph scale a graph should use
 */
abstract class GraphScale
{

    /**
     * Linear graph scale
     */
    const Linear = 'linear';

    /**
     * Logarithmic graph scale
     */
    const Logarithmic = 'log';

}

/// TODO save()
class Graph
{

    /**
     * The graph's internal id
     * @var integer
     */
    private $id;

    /**
     * The plugin this graph is for
     * @var Plugin
     */
    private $plugin;

    /**
     * The graph's name
     * @var string
     */
    private $name;

    /**
     * The graph's display name
     * @var string
     */
    private $displayName;

    /**
     * The type of graph to generate
     * @var GraphType
     */
    private $type;

    /**
     * If the graph is active
     * @var
     */
    private $active;

    /**
     * The graph's scale
     * @var string
     */
    private $scale;

    /**
     * The columns present in this graph
     * @var array
     */
    private $columns = array();

    /**
     * An array of the series objects
     * @var HighRollerSeriesData[]
     */
    private $series = array();

    public function __construct($id = -1, $plugin = NULL, $type = GraphType::Line, $name = '', $displayName = '', $active = 0, $scale = 'linear')
    {
        $this->id = $id;
        $this->plugin = $plugin;
        $this->type = $type;
        $this->name = $name;
        $this->displayName = $displayName;
        $this->active = $active;
        $this->scale = $scale;

        // If the display name is blank, use the internal name
        if ($displayName == '')
        {
            $this->displayName = $name;
        }

        if ($this->id >= 0)
        {
            // Load the columns present in the graph
            $this->loadColumns();
        }
    }

    /**
     * Save the graph to the database
     */
    public function save()
    {
        global $master_db_handle;

        $statement = $master_db_handle->prepare('UPDATE Graph SET DisplayName = ?, Type = ?, Active = ?, Scale = ? WHERE ID = ?');
        $statement->execute(array($this->displayName, $this->type, $this->active, $this->scale, $this->id)); // TODO
    }

    /**
     * Add some custom data to the graph
     *
     * @param $server Server
     * @param $columnName string
     * @param $value int
     */
    public function addCustomData($server, $columnName, $value)
    {
        global $master_db_handle;

        // get the id for the column
        $columnID = $this->getColumnID($columnName);

        $statement = $master_db_handle->prepare('INSERT INTO CustomData (Server, Plugin, ColumnID, DataPoint, Updated) VALUES (:Server, :Plugin, :ColumnID, :DataPoint, :Updated)
                                    ON DUPLICATE KEY UPDATE DataPoint = VALUES(DataPoint) , Updated = VALUES(Updated)');
        $statement->bindValue(':Server', intval($server->getID()), PDO::PARAM_INT);
        $statement->bindValue(':Plugin', intval($this->plugin->getID()), PDO::PARAM_INT);
        $statement->bindValue(':ColumnID', intval($columnID), PDO::PARAM_INT);
        $statement->bindValue(':DataPoint', intval($value), PDO::PARAM_INT);
        $statement->bindValue(':Updated', time(), PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Get or create a custom column and return the id
     *
     * @param $columnName string
     * @return int
     */
    public function getColumnID($columnName, $attemptedToCreate = false)
    {
        global $master_db_handle;

        // It should already be in the database
        $statement = get_slave_db_handle()->prepare('SELECT ID FROM CustomColumn WHERE Plugin = ? AND Graph = ? AND Name = ?');
        $statement->execute(array($this->plugin->getID(), $this->id, $columnName));

        if ($row = $statement->fetch())
        {
            $id = $row['ID'];
            return $id;
        }

        $statement = $master_db_handle->prepare('INSERT INTO CustomColumn (Plugin, Graph, Name) VALUES (:Plugin, :Graph, :Name)');
        $statement->execute(array(':Plugin' => $this->plugin->getID(), ':Graph' => $this->id, ':Name' => $columnName));

        // Now get the last inserted id
        $id = $master_db_handle->lastInsertId();
        return $id;
    }

    /**
     * Generate the graph to be printed out onto the page.
     * The generated code should be placed inside <script> tags.
     * @return string javascript
     */
    public function generateGraph($renderTo, $flags = array())
    {
        // Only generate the graph if we have plotters
        if (count($this->series) == 0)
        {
            return;
        }

        // We need to create a chart using the type
        $chart = NULL;

        // The graphing classname to use
        $classname = 'highstock';

        switch ($this->type)
        {
            case GraphType::Line:
                $chart = new HighRollerSplineChart();
                break;

            case GraphType::Area:
                $chart = new HighRollerAreaChart();
                break;

            case GraphType::Column:
                $chart = new HighRollerColumnChart();
                break;

            case GraphType::Pie:
                $chart = new HighRollerPieChart();
                $classname = 'highcharts';
                break;
        }

        // Nothing we can do if it's still null
        if ($chart === NULL)
        {
            return NULL;
        }

        // Set chart options
        $chart->chart->renderTo = $renderTo;
        $chart->chart->zoomType = 'x';

        // The title
        $chart->title->text = htmlentities($this->displayName); // $this->name;

        // Subtitle
        if ($this->plugin != null)
        {
            $chart->subtitle = array(
                'text' => 'for ' . htmlentities($this->plugin->getName()) . ' via http://mcstats.org'
            );
        } else
        {
            $chart->subtitle = array(
                'text' => 'via http://mcstats.org'
            );
        }

        // Disable credits
        $chart->credits = array('enabled' => false);

        // Non-pie graph specifics
        if ($this->type != GraphType::Pie)
        {
            $chart->rangeSelector = array(
                'selected' => ($this->type == GraphType::Column ? 0 : 3),
                'buttons' => array(
                    array(
                        'type' => 'hour',
                        'count' => 2,
                        'text' => '2h'
                    ), array(
                        'type' => 'hour',
                        'count' => 12,
                        'text' => '12h'
                    ), array(
                        'type' => 'day',
                        'count' => 1,
                        'text' => '1d'
                    ), array(
                        'type' => 'week',
                        'count' => 1,
                        'text' => '1w'
                    ), array(
                        'type' => 'week',
                        'count' => 2,
                        'text' => '2w'
                    ), array(
                        'type' => 'month',
                        'count' => 1,
                        'text' => '1m'
                    ), array(
                        'type' => 'month',
                        'count' => 6,
                        'text' => '6m'
                    ), array(
                        'type' => 'year',
                        'count' => 1,
                        'text' => '1y'
                    ), array(
                        'type' => 'all',
                        'text' => 'all'
                    )
                )
            );

            $chart->xAxis = array(
                'type' => 'datetime',
                'maxZoom' => 2 * 60,
                'dateTimeLabelFormats' => array(
                    'month' => '%e. %b',
                    'year' => '%b'
                ),
                'gridLineWidth' => 0
            );

            // Calculate the minimum value
            // TODO functionize
            $min = PHP_INT_MAX;

            foreach ($this->series as $series)
            {
                foreach ($series->data as &$a)
                {
                    foreach ($a as $epoch => $value)
                    {
                        $min = min($min, $value);
                    }
                }
            }

            $chart->yAxis = array(
                'min' => 0,
                'title' => array('text' => ''),
                'labels' => array(
                    'align' => 'left',
                    'x' => 3,
                    'y' => 16
                ),
                'showFirstLabel' => false
            );

            // Should we make the graph log?
            if ($this->scale == GraphScale::Logarithmic)
            {
                $chart->yAxis['type'] = 'logarithmic';
                $chart->yAxis['minorTickInterval'] = 'auto';
                unset($chart->yAxis['min']);
            }
        }

        // Tooltip + plotOptions
        if ($this->type != GraphType::Pie)
        {
            $chart->tooltip = array(
                'shared' => true,
                'crosshairs' => true
            );

            // TODO plot options
        } else // Pie
        {
            $chart->plotOptions = array(
                'pie' => array(
                    'allowPointSelect' => true,
                    'cursor' => 'pointer'
                )
            );
        }

        // Add each series to the chart
        foreach ($this->series as $series)
        {
            $chart->addSeries($series);
        }

        $isPlayerChart = $renderTo == 'GlobalServerChart' || $renderTo == 'PlayerServerChart';
        if ($isPlayerChart)
        {
            $flags = array(array(
                'type' => 'flags',
                'name' => '',
                'data' => array (
                    array (
                        'x' => 1341257400000,
                        'title' => '!',
                        'text' => 'minecraft.net login server outage'
                    )
                ),
                'color' => '#5F86B3',
                'fillColor' => '#5F86B3',
                'style' => array (
                    'color' => 'white'
                ),
                'states' => array (
                    'hover' => array (
                        'fillColor' => '#395C84'
                    )
                ),
                'onSeries' => 'Players',
                'shape' => 'squarepin',
                'width' => 12
            ));
        }

        foreach ($flags as $flag)
        {
            $chart->series[] = $flag;
        }

        // Some raw javascript
        $rawJavascript = '';

        if ($this->type != GraphType::Pie)
        {

            if (!$isPlayerChart)
            {
                // just sorts the series
                $rawJavascript = "
                    $renderTo.tooltip =
                    {
                        \"shared\": true,
                        \"crosshairs\": true,
                        \"formatter\": function() {
                            var points = this.points;
                            var series = points[0].series;
                            var s = series.tooltipHeaderFormatter(points[0].key);

                            var sortedPoints = points.sort(function(a, b){
                                return ((a.y < b.y) ? 1 : ((a.y > b.y) ? -1 : 0));
                            });

                            $.each(sortedPoints , function(i, point) {
                                s += point.point.tooltipFormatter(series.tooltipOptions.pointFormat);
                            });

                            return s;
                        }
                    };
                ";
            }
        } else
        { // Pie chart

            $rawJavascript = "
                $renderTo.plotOptions =
                {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            color: '#000000',
                            connectorColor: '#000000',
                            formatter: function() {
                                return '<b>' + this.point.name + '</b>: ' + ( Math.round(this.percentage * 10) / 10 ) + ' %';
                            }
                        }
                    }
                };
                $renderTo.tooltip =
                {
                    \"formatter\": function() {
                        return '<b>' + this.point.name + '</b>: ' + ( Math.round(this.percentage * 100) / 100 ) + ' %';
                    }
                };
            ";

        }

        // Render it!!
        return $chart->renderChart($classname, $rawJavascript);
    }

    /**
     * Get the columns present in the graph
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Load the columns for this graph
     * @param $limit_results Only show the most used results, mainly just for displaying in /plugin/
     */
    private function loadColumns($limit_results = false)
    {
        $this->columns = array();
        $statement = get_slave_db_handle()->prepare('SELECT ID, Name FROM CustomColumn WHERE Plugin = ? AND Graph = ?');
        $statement->execute(array($this->plugin->getID(), $this->id));

        while (($row = $statement->fetch()) != null)
        {
            $id = $row['ID'];
            $name = $row['Name'];
            $this->columns[$id] = $name;
        }
    }

    /**
     * Add a raw series to the graph
     * @param $series HighRollerSeriesData
     */
    public function addSeries($series)
    {
        $this->series[] = $series;
    }

    /**
     * Set the name of the graph
     * @param $name string
     */
    public function setName($name)
    {
        $this->name = $name;

        // Set the display name if the internal name is blank
        if ($this->displayName == '')
        {
            $this->displayName = $name;
        }
    }

    /**
     * Set the display name for the graph
     * @param $displayName string
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Set the name of the graph
     * @param $name
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Set if the graph is active or not
     * @param $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get the graph's scale
     * @param $scale string
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * Get the graph's internal id
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the plugin this graph is for
     * @return Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Get the graph's type
     * @return GraphType|int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the graph's name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the graph's display name
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Check if the graph is currently active
     * @return bool
     */
    public function isActive()
    {
        return $this->active == 1;
    }

    /**
     * Get the graph's scale
     * @return string
     */
    public function getScale()
    {
        return $this->scale;
    }

}