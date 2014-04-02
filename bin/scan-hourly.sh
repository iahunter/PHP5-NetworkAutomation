#!/bin/bash
###ps aux | grep php | ungrep bgp | ungrep grep | awk '{print $2}' | sort | uniq | xargs -n 1 kill
/opt/networkautomation/bin/poller-log.php --log="rescanning unreachable devices"
rm /root/.ssh/known_hosts  2>&1 | grep -v such

/opt/networkautomation/bin/fork-scan.php --stringfield2=none > /dev/null
/opt/networkautomation/bin/poller-log.php --log="unreachable device scan complete"
