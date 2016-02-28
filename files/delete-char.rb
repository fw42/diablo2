#!/usr/bin/env ruby
# 
# delete-char.rb
# takes one ore more character-names as input and outputs
# the filepaths which can be piped to rm in order to delete the 
# character
#
# Written by: 	Johannes Gilger <heipei@hackvalue.de>
# Date: 		2006-08-31
# License:		GPL
#
# Visit http://classic-addiction.game-server.cc for more information

chars = ARGV
prefix = "/home/diablo/var/"	# Change this to match your /var dir!
charsave_location = prefix + "charsave"
charinfo_location = prefix + "charinfo"

chars.each {|char|

char = char.downcase
begin
	File.open(charsave_location + "/" + char)
	puts charsave_location + "/" + char
rescue
end
begin
	charinfo_handle = Dir.open(charinfo_location)
	charinfo_handle.each {|charinfo|
		if (charinfo != "." && charinfo != "..")
			charinfo_accounts = Dir.open(charinfo_location + "/" + charinfo)
			charinfo_accounts.each {|charinfo_account|
				if (charinfo_account != "." && charinfo_account != "..")
					if (charinfo_account == char)
						puts charinfo_location + "/" + charinfo + "/" + char
					end
				end
			}
		end
	}
	
rescue
end
}

