#!/bin/bash
###ps aux | grep php | ungrep bgp | ungrep grep | awk '{print $2}' | sort | uniq | xargs -n 1 kill
/opt/networkautomation/bin/poller-log.php --log="running daily full scan"
rm /root/.ssh/known_hosts  2>&1 | grep -v such

/opt/networkautomation/bin/fork-scan.php > /dev/null
/opt/networkautomation/bin/poller-log.php --log="daily full scan complete"
/opt/networkautomation/bin/scan-hourly.sh
