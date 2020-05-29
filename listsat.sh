#!/bin/bash

/usr/bin/curl -s "127.0.0.1:14002/api/sno/" | /usr/bin/jq '.satellites[] | {"{#SAT}": .id, "{#URL}": .url}' | /usr/bin/jq --slurp '{data: .}'
