#!/bin/bash
#


# Setup.start
#

HOLD_DAYS=7
TIMESTAMP=$(date +"%F")
BACKUP_DIR="$HOME/www/_BACKUP_MySQL"

MYSQL_USR="web32"
MYSQL_PWD="!1WasySql"
#
# Setup.end


# Check and auto-repair all databases first
#
echo
echo "Checking all databases - this can take a while ..."
# mysqlcheck -u $MYSQL_USR --password=$MYSQL_PWD --auto-repair --all-databases  // not now

# Backup
#
echo
echo "Starting backup ..."
mkdir -p "$BACKUP_DIR/$TIMESTAMP"
databases="$(mysql -u $MYSQL_USR --password=$MYSQL_PWD -Bse'show databases')"


for db in $databases;
do
  if [ "$db" == "information_schema" ];
  then
    echo "No Dumping $db ..."
  fi
  if [ "$db" != "information_schema"  ]
  then
    echo "Dumping $db ..."
    mysqldump --force --opt -u $MYSQL_USR --password=$MYSQL_PWD --databases "$db" | gzip > "$BACKUP_DIR/$TIMESTAMP/$db.gz"
  fi
done

echo
echo "Cleaning up ..."
find $BACKUP_DIR -maxdepth 1 -mindepth 1 -mtime $HOLD_DAYS -type d -exec rm -rf {} \;
echo "-- DONE!"