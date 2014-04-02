]#!/bin/bash
###ps aux | grep php | ungrep bgp | ungrep grep | awk '{print $2}' | sort | uniq | xargs -n 1 kill

/opt/networkautomation/bin/poller-log.php --log="config grab started"

rm /tftpboot/config/*  2>&1 | grep -v such
rm /root/.ssh/known_hosts  2>&1 | grep -v such
rm -f /home/jmlavoie/.ssh/known_hosts  2>&1 | grep -v such

cd /opt/networkautomation/config

#/opt/networkautomation/bin/fork-config.php > /dev/null
/opt/networkautomation/bin/fork-config.php

svn add * 2>&1 | grep -v already
svn status
svn commit --username svnuser --password svnpass -m "autoupdated"
/opt/networkautomation/bin/poller-log.php --log="config grab done"
/opt/networkautomation/bin/scan-hourly.sh
