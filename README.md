# Plugin Metrics

While other statistical collecting tools for Bukkit plugins provided data that wasn't bad, the other problem with them was that they were not open source. Plugin metrics fills that gap with even more powerful stats behind it.

What it lacks in visual design, it makes up for in funtionality. Data your plugin can obtain ranges from basic data such as server/player counts to server-country data, and also can even include **custom data** that is **unique to your plugin**, which is also fully graphed for you.

As for server identification, No IPs will ever be stored in a database. Instead, servers are identified by a plugin-generated GUID. Country identification is done by a lighttpd GeoIP module utilizing MaxMind's GeoLite City IP database.

For a live example, please see [LWC](http://metrics.griefcraft.com/plugin/LWC)