## Preamble

Before using this in your plugin, please keep the following in mind and abide by these two simple rules:

1. You are free to modify the code as you wish. However, if you must set the update interval below 5 minutes, please contact Hidendra first. Not following this rule may mean updates from your plugin are blocked entirely.
2. This service is **free** - while downtime may be unexpected, I will do my best to get any affected services online ASAP.

With the simple formalities out of the way, submitting stats to metrics is extremely easy. Use the `Metrics.java` file found in this same folder in your plugin and make sure it is in **your own package** to prevent conflicts with other plugins.

**Regarding opt-out/guid/etc:** This is done **automatically** internally so there is no work for you to do besides what is below. To opt-out, a user must edit the `plugins/PluginMetrics/config.yml` file and change `opt-out: false` to `out-out: true`

## Creating a plugin on the website

When your plugin first connects it will create it automatically. If you want the author tag set, or your plugin hidden from the main page, please contact me.

## Usage

    try {
        // create a new metrics object
        Metrics metrics = new Metrics();

        // 'this' in this context is the Plugin object
        metrics.beginMeasuringPlugin(this);
    } catch (IOException e) {
        // Failed to submit the stats :-(
    }