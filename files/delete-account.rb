#!/usr/bin/env ruby
#
# delete-account.rb
# takes one ore more account names as input and prints the filenames which have to
# be deleted in order to fully remove the account
# output can be piped to rm -r as an example
#
# Written by: 	Johannes Gilger <heipei@hackvalue.de>
# Date: 		2006-08-31
# License:		GPL
#
# Visit http://classic-addiction.game-server.cc for more information

accounts = ARGV
prefix = "/home/diablo/var/"	# Change this to match your /var dir!
users_location = prefix + "users"

accounts.each {|account|

account = account.downcase

begin
	account_location = prefix + "charinfo/" + account
	account_handle = Dir.open(account_location)
	users = Dir.open(users_location)
	users_array = Dir.entries(users_location)
	puts account_location
	account_handle.each {|account_handle_file|
		if (account_handle_file != ".." && account_handle_file != ".") 
			puts prefix + "charsave/" + account_handle_file
		end
	}
	users.each {|user|
		if (account == user.downcase)
			puts prefix + "users/" + user
		end
	}
rescue
end
}

