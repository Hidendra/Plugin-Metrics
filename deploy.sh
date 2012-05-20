#!/bin/bash

# rsync command
RSYNC="rsync -avzq --progress"

# nginx load balancer
NGINX_BALANCER="root@mcstats.org"

cd src
chmod -R 755 .

# First deploy to the load balancer
echo "Deploying to nginx load balancer"

# First the main website
$RSYNC  --exclude 'static/' --exclude 'config.php' ./ $NGINX_BALANCER:/var/www/servers/mcstats.org/

echo -e " \e[0;32m=>\e[00m Main content"

# Static content
cd static
$RSYNC ./ $NGINX_BALANCER:/var/www/servers/static.mcstats.org
cd ..

echo -e " \e[0;32m=>\e[00m Static content"
