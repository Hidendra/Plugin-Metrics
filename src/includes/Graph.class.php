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
     * The columns present in this graph
     * @var array
     */
    private $columns = array();

    /**
     * An array of the series objects
     * @var HighRollerSeriesData[]
     */
    private $series = array();

    public function __construct($id = -1, $plugin = NULL, $type = GraphType::Line, $name = '', $active = 0)
    {
        $this->id = $id;
        $this->plugin = $plugin;
        $this->type = $type;
        $this->name = $name;
        $this->active = $active;

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
        global $pdo;

        $statement = $pdo->prepare('UPDATE Graph SET Type = ?, Active = ? WHERE ID = ?');
        $statement->execute(array($this->type, $this->active, $this->id));
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
        global $pdo;

        // get the id for the column
        $columnID = $this->getColumnID($columnName);

        // Does the server already have a data point for this column?
        $statement = $pdo->prepare('SELECT ID FROM CustomData WHERE Server = :Server AND Plugin = :Plugin AND ColumnID = :ColumnID');
        $statement->execute(array(':Server' => $server->getID(), ':Plugin' => $this->plugin->getID(), ':ColumnID' => $columnID));

        // If we found it, update it instead
        if ($row = $statement->fetch()) {
            $id = $row['ID'];

            $statement = $pdo->prepare('UPDATE CustomData SET DataPoint = :DataPoint, Updated = :Updated WHERE ID = :ID');
            $statement->execute(array(':DataPoint' => $value, ':Updated' => time(), ':ID' => $id));
            return;
        }

        // Not there yet, insert it
        $statement = $pdo->prepare('INSERT INTO CustomData (Server, Plugin, ColumnID, DataPoint, Updated) VALUES (:Server, :Plugin, :ColumnID, :DataPoint, :Updated)');
        $statement->execute(array(
            ':Server' => $server->getID(),
            ':Plugin' => $this->plugin->getID(),
            ':ColumnID' => $columnID,
            ':DataPoint' => $value,
            ':Updated' => time()
        ));
    }

    /**
     * Verify a column exists and create it if it does not
     *
     * @param $columName
     */
    public function verifyColumn($columnName, $attemptedToCreate = false, $updateColumn = true) {
        global $pdo;

        $statement = $pdo->prepare('SELECT ID, Graph FROM CustomColumn WHERE Plugin = ? AND Name = ?');
        $statement->execute(array($this->plugin->getID(), $columnName));

        // Did we get it?
        if ($row = $statement->fetch())
        {
            $id = $row['ID'];
            $graphID = $row['Graph'];

            if (!$updateColumn)
            {
                // This is as far as we need to go
                return;
            }

            // Is it already assigned to a graph?
            if ($id > 0)
            {
                return;
            }

            // Update it if it does not match
            if ($graphID != $this->id)
            {
                $statement = $pdo->prepare('UPDATE CustomColumn SET Graph = ? WHERE ID = ?');
                $statement->execute(array($this->id, $id));

                // Reload the columns
                $this->loadColumns();
            }

            return;
        }

        if ($attemptedToCreate)
        {
            error_fquit("Failed to create custom column: $columnName");
        }

        // Nope...
        $statement = $pdo->prepare('INSERT INTO CustomColumn (Plugin, Graph, Name) VALUES (:Plugin, :Graph, :Name)');
        $statement->execute(array(':Plugin' => $this->plugin->getID(), ':Graph' => $this->id, ':Name' => $columnName));

        $this->verifyColumn($columnName, TRUE, $updateColumn);
    }

    /**
     * Get or create a custom column and return the id
     *
     * @param $columnName string
     * @return int
     */
    public function getColumnID($columnName, $attemptedToCreate = false)
    {
        global $pdo;

        // It should already be in the database
        $statement = $pdo->prepare('SELECT ID FROM CustomColumn WHERE Plugin = ? AND Graph = ? AND Name = ?');
        $statement->execute(array($this->plugin->getID(), $this->id, $columnName));

        if ($row = $statement->fetch())
        {
            return $row['ID'];
        }

        $statement = $pdo->prepare('SELECT ID FROM CustomColumn WHERE Plugin = ? AND Name = ?');
        $statement->execute(array($this->plugin->getID(), $columnName));

        if ($row = $statement->fetch())
        {
            return $row['ID'];
        }

        error_fquit('getColumnID() failed : "' . $columnName . '" in graph ' . $this->id);
    }

    /**
     * Generate the graph to be printed out onto the page.
     * The generated code should be placed inside <script> tags.
     * @return string javascript
     */
    public function generateGraph($renderTo)
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
        $chart->title->text = $this->name;

        // Subtitle
        if ($this->plugin != null)
        {
            $chart->subtitle = array(
                'text' => 'for ' . $this->plugin->getName() . ' via http://metrics.griefcraft.com'
            );
        } else
        {
            $chart->subtitle = array(
                'text' => 'via http://metrics.griefcraft.com'
            );
        }

        // Disable credits
        $chart->credits = array('enabled' => false);

        // Non-pie graph specifics
        if ($this->type != GraphType::Pie)
        {
            $chart->rangeSelector = array(
                'selected' => 3,
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

        // Render it!!
        return $chart->renderChart($classname);
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
     */
    private function loadColumns()
    {
        global $pdo;
        $this->columns = array();
        $statement = $pdo->prepare('SELECT ID, Name FROM CustomColumn WHERE Graph = ? AND Plugin = ?');
        $statement->execute(array($this->id, $this->plugin->getID()));

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
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * Check if the graph is currently active
     * @return bool
     */
    public function isActive()
    {
        return $this->active == 1 ? true : false;
    }

}