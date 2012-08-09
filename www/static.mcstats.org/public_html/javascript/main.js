
$(document).ready(function() {
    // Hide the servers that are waiting to be hidden
    $(".hide-server").hide();

    // listen for graph generator updates
    setInterval(function() {
        $.get('/graph-generator.php', function(data) {
            var graphPercent = parseInt(data);

            // nothing generating
            if (graphPercent == 0) {
                $("#graph-generator").hide();
            }

            // graphs generating
            else {
                $("#graph-generator-progress-bar").width(graphPercent + "%");
                $("#graph-generator").show();
            }
        });
    }, 2000);
});

// plugin list vars
var pluginListPage = 1;
var pluginListMaxPages = 1;

/**
 * Load a page in the plugin list
 * @param page
 */
function loadPluginListPage(page) {
    if (page < 1) {
        return;
    }

    loadMaxPagesFromHTML();

    if (page > pluginListMaxPages) {
        page = pluginListMaxPages;
    }

    // disable the plugin buttons before sending data
    $("#plugin-list-back").addClass("disabled");
    $("#plugin-list-forward").addClass("disabled");
    $("#plugin-list-go").addClass("disabled");

    // load the json data
    var json = $.getJSON("/api/1.0/list/" + page, function(data) {
        // var to the store the html in
        var html = "";

        for (i = 0; i < data.plugins.length; i++) {
            var plugin = data.plugins[i];
            var rank = plugin.rank;

            if (rank <= 10) {
                rank = "<b>" + rank + "</b>";
                plugin.name = "<b>" + plugin.name + "</b>";
                plugin.servers24 = "<b>" + plugin.servers24 + "</b>";
            }

            html += '<tr id="plugin-list-item"> <td style="text-align: center;">' + rank + ' </td> <td> <a href="/plugin/' + plugin.name + '" target="_blank">' + plugin.name + '</a> </td> <td style="text-align: center;"> ' + plugin.servers24 + ' </td> </tr>';
        }

        // clear out the old plugins in the table
        clearPluginList();

        // add it to the table
        $("#plugin-list tr:first").after(html);

        // loaded !
        pluginListPage = page;

        // update page number displays
        $("#plugin-list-current-page").html(page);
        $("#plugin-list-max-pages").html(data.maxPages);
        $("#plugin-list-goto-page").val(page);

        // re-enable the plugin buttons
        $("#plugin-list-back").removeClass("disabled");
        $("#plugin-list-forward").removeClass("disabled");
        $("#plugin-list-go").removeClass("disabled");

        // show/hide the back button as necessary
        if (pluginListPage == 1) {
            $("#plugin-list-back").hide();
        } else {
            $("#plugin-list-back").show();
        }

        // and the forward button
        if (pluginListPage == pluginListMaxPages) {
            $("#plugin-list-forward").hide();
        } else {
            $("#plugin-list-forward").show();
        }

        // change the URL
        history.pushState(null, "Plugin Metrics :: Page " + page, "/plugin-list/" + page + "/");
    });

}

/**
 * Clear the plugin list
 */
function clearPluginList() {
    $("#plugin-list #plugin-list-item").remove();
}

/**
 * Move the plugin list backwards
 */
function movePluginListBack() {
    loadCurrentPageFromHTML();

    if (pluginListPage == 1) {
        return;
    }

    loadPluginListPage(pluginListPage - 1);
}

/**
 * Move the plugin list forwards
 */
function movePluginListForward() {
    loadCurrentPageFromHTML();

    // go to the next page
    loadPluginListPage(pluginListPage + 1);
}

function loadMaxPagesFromHTML() {
    var maxPageHTML = parseInt($("#plugin-list-max-pages").html());

    if (maxPageHTML > 0) {
        pluginListMaxPages = maxPageHTML;
    }
}

/**
 * attempt to load the current page from the html
 */
function loadCurrentPageFromHTML() {
    var currentPageHTML = parseInt($("#plugin-list-current-page").html());

    if (currentPageHTML > 0) {
        pluginListPage = currentPageHTML;
    }
}

/**
 * Show all of the hidden servers (mainly used on the index page)
 */
function showMoreServers() {
    // Hide the show more link
    $(".more-servers").hide();

    // Show the servers
    $(".hide-server").show();
}