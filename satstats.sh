#!/bin/bash

if [ $# -ne 2 ]; then
	echo $0 satellite query
	exit 1
fi

query=`echo "$2" | tr '^' '[' | tr '%' ']' `

querymath=`echo "$query" | grep fail | wc -l`

if [ $querymath -eq 1 ]; then
	w=`echo "$query" | cut -d"." -f 1`
	success=`/usr/bin/curl -s "127.0.0.1:14002/api/sno/satellite/$1" | /usr/bin/jq ".${w}.successCount"`
	total=`/usr/bin/curl -s "127.0.0.1:14002/api/sno/satellite/$1" | /usr/bin/jq ".${w}.totalCount"`
	echo "$total - $success" | bc
else
	result=`/usr/bin/curl -s "127.0.0.1:14002/api/sno/satellite/$1" | /usr/bin/jq "$query"`
	atr=`echo "$query" | grep atRest | wc -l`

	if [ $atr -eq 1 ]; then
		echo "$result" | cut -d'.' -f 1
	else
		echo "$result"
	fi 
fi
