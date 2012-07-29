#!/bin/bash

if [ $# -ne 2 ]; then
    echo "Usage: $0 <realm> <web|db>"
    exit
fi

REALM="$1"
TYPE_SHORT="$2"

if [ "$TYPE_SHORT" = "web" ]; then
	TYPE="web-frontend"
elif [ "$TYPE_SHORT" = "db" ]; then
	TYPE="database"
else
	echo "Usage: $0 <realm> <web|db>"
	exit
fi

# rsync command
RSYNC="rsync -avzq --progress"

REMOTE_HOST="root@mcstats.org"
REMOTE_LOCATION="/"

if [ "$REALM" == "live" ]; then
	REMOTE_HOST="root@mcstats.org"
elif [ "$REALM" == "dev" ]; then
	REMOTE_HOST="root@192.168.1.30"
else
    REMOTE_HOST="root@10.10.1.30"
fi

echo -e "Realm:\t\033[0;32m$REALM\033[00m"
echo -e "Type:\t\033[0;32m$TYPE\033[00m"

if [ -d "backend" ]; then
	cd backend/freebsd-base/"$TYPE"
else
	cd ../backend/freebsd-base/"$TYPE"
fi

if [ "$REALM" == "live" ] || [ "$REALM" == "dev" ]; then
    $RSYNC ./ $REMOTE_HOST:"$REMOTE_LOCATION"
else
    $RSYNC -e "ssh root@zero.mcstats.org ssh" ./ $REMOTE_HOST:"$REMOTE_LOCATION"
fi

echo -e " \033[0;32m=>\033[00m Done"