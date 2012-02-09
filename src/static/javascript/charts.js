// Global stats
var globalStatistics;
var globalStatisticsOptions;

// Country pie chart
var countryPieChart;
var countryPieChartOptions;

// Custom graphing
var customGraph;
var customGraphOptions;

/**
 * Convert epoch time to a Date object to be used for graphing
 * @param epoch
 * @return Date
 */
function epochToDate(epoch)
{
    var date = new Date(0);
    date.setUTCSeconds(epoch);
    return date;
}

function generateCustomData()
{
    $.getJSON('/timeline-custom/' + pluginName + '/144', function(json) {
        var columnNames = {};
        var columnData = {}; // columnData[id] = [date, xx, yy...]

        // Add the columns
        $.each(json.columns, function(i, v) {
            columnNames[i] = v;
            columnData[i] = [];
        });

        // iterate through the JSON data
        $.each(json.data, function(i, v) {
            // The graph row
            var date = Date.parse(epochToDate(parseInt(i)));

            // Generate the data into the map
            $.each(v, function(i, v) {
                columnData[i].push([date, parseInt(v)]);
            });
        });

        // Add the data to the graph
        $.each(columnData, function(id, data) {
            customGraphOptions.series.push(
                {
                    name: columnNames[id],
                    marker: {
                        radius: 0
                    },
                    data: data
                }
            );
        });

        customGraphOptions.title.text = 'Custom data for ' + pluginName;
        customGraph = new Highcharts.Chart(customGraphOptions);
    });
}

/**
 * Generate the timeline coverage for player/server counts
 */
function generateCoverage()
{
    $.getJSON('/coverage/' + pluginName + '/144', function(json) {
        // Store all of the extracted data in an arrow
        var allServers = [];
        var allPlayers = [];

        // iterate through the JSON data
        $.each(json, function(i, v) {
            // extract data
            var date = Date.parse(epochToDate(parseInt(v.epoch)));
            var servers = parseInt(v.servers);
            var players = parseInt(v.players);

            // add it to the graph
            allServers.push([date, servers]);
            allPlayers.push([date, players]);
        });

        globalStatisticsOptions.series.push({
            name: 'Active Servers',
            marker: {
                radius: 0
            },
            data: allServers
        });

        globalStatisticsOptions.series.push({
            name: 'Active Players',
            marker: {
                radius: 0
            },
            data: allPlayers
        });
        globalStatisticsOptions.title.text = 'Global Statistics for ' + pluginName;
        globalStatistics = new Highcharts.Chart(globalStatisticsOptions);
    });
}

/**
 * Generates the pie chart for country data
 */
function generateCountryPieChart()
{
    $.getJSON('/country-piechart/' + pluginName, function(json) {
        var data = [];

        // iterate through the JSON data
        $.each(json, function(i, v) {
            var country = i;
            var servers = v;

            data.push([country, servers]);
        });

        countryPieChartOptions.series.push({
            type: 'pie',
            name: 'Country',
            data: data
        });

        countryPieChartOptions.title.text = 'Country Statistics for ' + pluginName;
        countryPieChart = new Highcharts.Chart(countryPieChartOptions);
    });
}

$(document).ready(function() {

    // GLOBAL STATISTICS
    globalStatisticsOptions = {

        chart: {
            renderTo: 'coverage_timeline',
            type: 'spline',
            zoomType: 'x'
        },

        title: {
            text: 'Global Statistics'
        },

        subtitle: {
            text: 'via http://metrics.griefcraft.com'
        },

        credits: {
            enabled: false
        },

        xAxis: {
            type: 'datetime',
            maxZoom: 1 * 3600000,
            dateTimeLabelFormats: { // don't display the dummy year
                month: '%e. %b',
                year: '%b'
            },
            gridLineWidth: 0
        },

        yAxis: { // left y axis
            min: 0,
            title: {
                text: null
            },
            labels: {
                align: 'left',
                x: 3,
                y: 16,
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0);
                }
            },
            showFirstLabel: false
        },

        legend: {
            align: 'left',
            verticalAlign: 'top',
            y: 25,
            floating: true,
            borderWidth: 0
        },

        tooltip: {
            shared: true,
            crosshairs: true
        },

        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function() {
                            hs.htmlExpand(null, {
                                pageOrigin: {
                                    x: this.pageX,
                                    y: this.pageY
                                },
                                headingText: this.series.name,
                                maincontentText: Highcharts.dateFormat('%A, %b %e, %Y', this.x) +':<br/> '+
                                    this.y +' visits',
                                width: 200
                            });
                        }
                    }
                },
                marker: {
                    lineWidth: 1
                }
            }
        },

        series: []
    };

    // CUSTOM GRAPH
    customGraphOptions = {

        chart: {
            renderTo: 'custom_timeline',
            type: 'spline',
            zoomType: 'x'
        },

        title: {
            text: 'Custom Graphing'
        },

        subtitle: {
            text: 'via http://metrics.griefcraft.com'
        },

        credits: {
            enabled: false
        },

        xAxis: {
            type: 'datetime',
            maxZoom: 1 * 3600000,
            dateTimeLabelFormats: { // don't display the dummy year
                month: '%e. %b',
                year: '%b'
            },
            gridLineWidth: 0
        },

        yAxis: { // left y axis
            min: 0,
            title: {
                text: null
            },
            labels: {
                align: 'left',
                x: 3,
                y: 16,
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0);
                }
            },
            showFirstLabel: false
        },

        legend: {
            align: 'left',
            verticalAlign: 'top',
            y: 25,
            floating: true,
            borderWidth: 0
        },

        tooltip: {
            shared: true,
            crosshairs: true
        },

        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function() {
                            hs.htmlExpand(null, {
                                pageOrigin: {
                                    x: this.pageX,
                                    y: this.pageY
                                },
                                headingText: this.series.name,
                                maincontentText: Highcharts.dateFormat('%A, %b %e, %Y', this.x) +':<br/> '+
                                    this.y +' visits',
                                width: 200
                            });
                        }
                    }
                },
                marker: {
                    lineWidth: 1
                }
            }
        },

        series: []
    };

    countryPieChartOptions = {
        chart: {
            renderTo: 'country_piechart',
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Country Statistics'
        },
        subtitle: {
            text: 'via http://metrics.griefcraft.com'
        },
        tooltip: {
            formatter: function() {
                return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(1) +' %';
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    color: Highcharts.theme.textColor || '#000000',
                    connectorColor: Highcharts.theme.textColor || '#000000',
                    formatter: function() {
                        return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(1) +' %';
                    }
                }
            }
        },
        series: []
    };

    // Generate stats!
    generateCoverage();
    generateCountryPieChart();
});