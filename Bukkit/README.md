## Preamble

Before using this in your plugin, please keep the following in mind when deciding if you want to use this:

1. You are free to modify the code as you wish. However, if you must set the update interval below 5 minutes, please contact Hidendra first. Not following this may mean updates from your plugin are blocked entirely.
2. This service is **free** and I cannot guarantee 100% uptime, so any downtime should be regarded as unavoidable.

Submitting stats to metrics is extremely easy. Add the `Metrics.java` file found in this same folder in your plugin and make sure it is in **your own package** to prevent conflicts with other plugins. Move on to the examples below to see how to implement it.

**Regarding opt-out/guid/etc:** This is done **automatically** internally so there is no work for you to do besides what is below. To opt-out, a user must edit the `plugins/PluginMetrics/config.yml` file and change `opt-out: false` to `out-out: true`

## Creating a plugin on the website

When your plugin first connects it will create it automatically on the website.

To be able to access your plugin through the [admin panel](http://metrics.griefcraft.com/admin/) you will need to contact Hidendra to get your account linked to your plugin. The best place to do this is irc: `irc.esper.net #metrics`


## Usage

    try {
        Metrics metrics = new Metrics(plugin);
        metrics.start();
    } catch (IOException e) {
        // Failed to submit the stats :-(
    }

## Custom Data

You can also submit your own custom data that can be graphed, as seen [here](http://metrics.griefcraft.com/plugin/LWC). This is very easy and here is an example from LWC:

    try {
        Metrics metrics = new Metrics(plugin);

        // Plot the total amount of protections
        metrics.addCustomData(new Metrics.Plotter("Total Protections") {

            @Override
            public int getValue() {
                return physicalDatabase.getProtectionCount();
            }

        });

        metrics.start();
    } catch (IOException e) {
        log(e.getMessage());
    }

## Custom graphs

While you can send in custom data, there is another very powerful feature available: Multiple graphs.

Currently, metrics provides the ability to create a couple different types of graphs, including Line, Area, Pie, and Column. The TYPE of graph can be set from the [admin panel](http://metrics.griefcraft.com/admin/) once you've gotten access to it for your plugin.

Here is a simple example which creates a Pie graph (once changed on the website):

    try {
        Metrics metrics = new Metrics(plugin);

        // Construct a graph, which can be immediately used and considered as valid
        Graph graph = metrics.createGraph("Percentage of weapons used");

        // Diamond sword
        graph.addPlotter(new Metrics.Plotter("Diamond Sword") {

                @Override
                public int getValue() {
                        return 4; // Number of players who used a diamond sword
                }

        });

        // Iron sword
        graph.addPlotter(new Metrics.Plotter("Iron Sword") {

                @Override
                public int getValue() {
                        return 17;
                }

        });

        metrics.start();
    } catch (IOException e) {
        log(e.getMessage());
    }

In addition, custom graphs will not automatically show up. You must go into the admin panel and change them from disabled to enabled. This is to prevent some problems down the road and also forces you to be aware of the functions that you can do on the website.