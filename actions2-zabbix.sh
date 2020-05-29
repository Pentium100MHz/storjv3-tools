#!/bin/bash

sat=$1
query=$2

res=`cat /opt/mon/actions2.json | jq ".\"$sat\".$query"`
nul=`echo "$res" | grep null | wc -l`

if [ $nul -eq 1 ]; then
	echo 0
else
	echo "$res"
fi
