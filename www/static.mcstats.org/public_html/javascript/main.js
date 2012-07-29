
$(document).ready(function() {
    // Hide the servers that are waiting to be hidden
    $(".hide-server").hide();
});

/**
 * Show all of the hidden servers (mainly used on the index page)
 */
function showMoreServers() {
    // Hide the show more link
    $(".more-servers").hide();

    // Show the servers
    $(".hide-server").show();
}