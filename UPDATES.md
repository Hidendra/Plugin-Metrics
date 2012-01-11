#### January 11, 2012

* A mis-design occurred where servers using multiple plugins that utilize these metrics would only allow 1 plugin to properly update.
* The fix employs moving the Plugin/Plugin Version/Updated metadata in the Server table to a new table named `ServerPlugin`
 * This means that the Server data contains purely information data about the server itself

Converting data from the new format only required 1 SQL query to be performed:

`INSERT INTO ServerPlugin (Server, Plugin, Version, Updated) SELECT Server.ID AS Server, Server.Plugin, Server.CurrentVersion AS Version, Server.Updated FROM Server;`

After this has been done, the unused columns on Server (Plugin, CurrentVersion, Updated) can be dropped.