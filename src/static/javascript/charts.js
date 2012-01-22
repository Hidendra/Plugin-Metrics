// Global stats
var globalStatistics;
var globalStatisticsOptions;

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
                radius: 3
            },
            data: allServers
        });

        globalStatisticsOptions.series.push({
            name: 'Active Players',
            marker: {
                radius: 3
            },
            data: allPlayers
        });
        globalStatisticsOptions.title.text = 'Global Statistics for ' + pluginName;
        globalStatistics = new Highcharts.Chart(globalStatisticsOptions);
    });
}

$(document).ready(function() {

    // GLOBAL STATISTICS
    globalStatisticsOptions = {

        chart: {
            renderTo: 'coverage_timeline'
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
            tickInterval: 1 * 3600 * 1000, // one hour
            tickWidth: 0,
            gridLineWidth: 0,
            labels: {
                align: 'left',
                x: 3,
                y: -3
            }
        },

        yAxis: [{ // left y axis
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
        }, { // right y axis
            linkedTo: 0,
            gridLineWidth: 0,
            opposite: true,
            title: {
                text: null
            },
            labels: {
                align: 'right',
                x: -3,
                y: 16,
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0);
                }
            },
            showFirstLabel: false
        }],

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
            renderTo: 'custom_timeline'
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
            tickInterval: 1 * 3600 * 1000, // one hour
            tickWidth: 0,
            gridLineWidth: 0,
            labels: {
                align: 'left',
                x: 3,
                y: -3
            }
        },

        yAxis: [{ // left y axis
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
        }, { // right y axis
            linkedTo: 0,
            gridLineWidth: 0,
            opposite: true,
            title: {
                text: null
            },
            labels: {
                align: 'right',
                x: -3,
                y: 16,
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0);
                }
            },
            showFirstLabel: false
        }],

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

    // Generate the global stats
    generateCoverage();
});