#!/bin/bash
curl -s https://gitweb.torproject.org/tor.git/plain/src/or/config.c | grep -Eo "([0-9]{1,3}\.){3}[0-9]{1,3}:" | sed 's/://' > /tmp/TOR_DIRSERVERS
