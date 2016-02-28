#!/bin/sh
./itemcounter | grep -vE "(0 soj|Total|^$)" | awk {'print $1" "$20" SoJ"'}
