#!/bin/sh
./itemcounter | grep "Total" | awk {'print $2" "$4" "$6" "$8" "$10" "$12" "$14" "$16" "$18" "$20'}
