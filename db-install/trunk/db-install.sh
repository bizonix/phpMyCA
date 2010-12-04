#!/bin/bash -e
#
# This script will iterate through /var/cache/sql/*.sql and run each sql script
# it locates there.  It assumes any sql scripts found there need to be run on
# the host this script is running on.  It will attempt to start the mysql
# server first.  It will prompt once for credentials and re-use them after.
# Each sql script it finds it will run only once, then empty the script itself
# in order to prevent leakage of sensitive information.
#
# A single argument is available: --runall.  If the --runall argument is
# provided it is assumed we want to import each sql script found, this is
# primarily for first-time installations.  If --runall is not an argument the
# user will be prompted before importing each sql script.
#
if [ "$1" == "--runall" ]; then
	RUNALL="yes"
fi

if [ ! -d /var/cache/sql ]; then
	echo "creating /var/cache/sql"
	mkdir -p /var/cache/sql
	chmod 700 /var/cache/sql
fi

#
# Import databases if needed...
#
list=`ls /var/cache/sql/*.sql 2>/dev/null`
if [ -z "$list" ]; then
	echo "No imports located in /var/cache/sql"
	exit 0
fi

for SQL in $list
do
	if [ -f "$SQL.done" ]; then
		echo "SKIP: $SQL"
		cat /dev/null > $SQL
		continue;
	fi
	if [ -z "$RUNALL" ]; then
		unset junk
		echo "SQL SCRIPT LOCATED: $SQL"
		echo -n 'To skip import, enter SKIP: '
		read junk
		if [ "$junk" == "SKIP" ]; then
			continue
		fi
	fi
	if [ -z "$USER_SET" ]; then
		echo -n "Enter MySQL username with admin privileges: "
		read MY_USER
		USER_SET="yes"
	fi
	if [ -z "$PASS_SET" ]; then
		echo -n "Enter MySQL password for $MY_USER: "
		read -s MY_PASS
		PASS_SET="yes"
	fi
	if [ -z "$MYSQL_STARTED" ]; then
		echo "Attempting to start mysql server"
		invoke-rc.d mysql start >/dev/null 2>&1
		if [ $? -ne 0 ]; then
			echo "Error: could not start mysql server"
			exit 1
		fi
		MYSQL_STARTED="yes"
	fi
	set +e
	echo "IMPORT: $SQL"
	if [ -n "$MY_PASS" ]; then
		mysql --user="$MY_USER" --password="$MY_PASS" < $SQL
	else
		mysql --user="$MY_USER" < $SQL
	fi
	if [ $? -ne 0 ]; then
		echo "ABORT: could not load $SQL, try again with better credentials"
		exit 1
	fi
	set -e
	cat /dev/null > $SQL
	cat /dev/null > "$SQL.done"
done
