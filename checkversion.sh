#!/bin/bash
t=`/usr/bin/curl -s "https://auth.docker.io/token?service=registry.docker.io&scope=repository:storjlabs/storagenode:pull" | cut -d'"' -f 4`
/usr/bin/curl -s -H "Authorization: Bearer $t" https://index.docker.io/v2/storjlabs/storagenode/manifests/latest | jq '.history[0].v1Compatibility | fromjson |.created'
