#!/bin/bash
for ip in {2..61}; do /sbin/ifconfig eth0 inet add 123.456.72.${ip}; done

