#!/bin/sh
#
# Startup Debian and Ubuntu script for BGPmon.
#
# Author: Mikhail Strizhov
# strizhov@cs.colostate.edu
#
### BEGIN INIT INFO
# Provides:          bgpmon
# Required-Start:    $syslog $local_fs $time
# Required-Stop:     $syslog $local_fs
# Default-Start:     2 3 4 5
# Default-Stop:	     0 1 6
# Short-Description: Start BGPmon
### END INIT INFO

# Using LSB funtions:
. /lib/lsb/init-functions


set -e

BGPMON=bgpmon
BGPMON_EXEC=/usr/local/bin/bgpmon 
CONFIG_FILE=/usr/local/etc/bgpmon_config.txt
PIDFILE=/var/run/bgpmon.pid
ARGS="-d -c $CONFIG_FILE -s"

case "$1" in
  start)
	if [ -f $PIDFILE ]
	then
		echo "$PIDFILE exists, $BGPMON is already running or crashed!"
	else
	        echo -n "Starting $BGPMON: "
		start-stop-daemon --start --quiet --exec $BGPMON_EXEC -- $ARGS
		log_end_msg $?
	fi	
        ;;
  stop)

	if [ ! -f $PIDFILE ]
	then
		echo "$PIDFILE does not exist, $BGPMON is not running!"
	else
        	echo -n "Shutting down $BGPMON, please be patient: "
		start-stop-daemon --stop --signal 0 --retry 5 --quiet --name $BGPMON
		log_end_msg $?
		rm $PIDFILE
	fi
        ;;
 *)
  	log_warning_msg "Usage: $BGPMON {start|stop}" >&2
        exit 1
esac

exit 0

