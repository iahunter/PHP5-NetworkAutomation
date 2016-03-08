#!/bin/bash
while (( 1 )); do
	echo "starting process!"
	tcpdump -l -n -i eth0 ip dst net 123.456.72.0/25 > /tmp/TCPDUMP & tail -f /tmp/TCPDUMP | /opt/networkautomation/bin/honeypot-parse-tcpdump.php
	killall tcpdump
	echo "process died, restarting!"
done
