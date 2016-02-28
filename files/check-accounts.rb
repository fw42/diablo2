#!/usr/bin/env ruby
# 
# check-accounts.rb
# Check Integrity of users, charinfo and charsave of a PvPGN Server
# also check if the permissions for all the files match
#
# Written by: 	Johannes Gilger <heipei@hackvalue.de>
# Date: 		2006-08-26
# License:		GPL
#
# Visit http://classic-addiction.game-server.cc for more information

@prefix = "/home/diablo/var/"	# Change this to match your /var dir!
@user_uid = 1002 				# UID for the user running diablo
@user_gid = 100 				# GID for this user
@users_dir_mode = 16877
@charinfo_dir_mode = 16877
@charsave_dir_mode = 16877
@users_mode = 33188
@charinfo_mode = 33206
@charsave_mode = 33206

users_location = @prefix + "users"
charinfo_location = @prefix + "charinfo"
charsave_location = @prefix + "charsave"

begin
	users = Dir.open(users_location)
	charinfos = Dir.open(charinfo_location)
	charsaves = Dir.open(charsave_location)
	users_array = Dir.entries(users_location)
	users_array.collect! {|user_array| user_array.downcase} # Necessary workaround
	charinfos_array = Dir.entries(charinfo_location)
	charsaves_array = Dir.entries(charsave_location)
rescue
	$stderr.puts $!
end

# Function for checking file permissions
def check_perm(filename, mode)
	begin
		file = File.new(filename)
	rescue
		$stderr.puts $!
		break
	end
	checkfile = file.stat
	#puts "DEBUG: " + file.path + checkfile.mode.to_s
	if not (checkfile.mode == mode)
		puts file.path + " has wrong permissions, should be " + mode.to_s + " but is "+ checkfile.mode.to_s + " instead!"
	end
	if not (checkfile.uid == @user_uid && checkfile.gid == @user_gid)
		puts file.path + " has the wrong ownership, should be " + @user_uid.to_s + ":" + @user_gid.to_s + " but is " + checkfile.uid.to_s + ":" + checkfile.gid.to_s + " instead!"
	end
	file.close
end

puts "Checking ..."

# check permissions of the 3 directories
check_perm(charinfo_location, @charinfo_dir_mode)
check_perm(users_location, @users_dir_mode)
check_perm(charsave_location, @charsave_dir_mode)

# check if users from users are in charinfo too (and permissions for users)
users.each {|user| 
	if (user != "." && user != "..")
		if (charinfos_array.include?(user.downcase) == false)
			puts user + " is not in " + charinfo_location +"!" 
		end 
		check_perm((users_location + "/" + user), @users_mode)
	end
}

# chech if chars from charinfo/<user>/ are in charsave (and permissions for charinfo/*/*)
@charinfo_all_users = Array.new

charinfos.each { |charinfo|
	if (charinfo != "." && charinfo != "..") 
		charinfo_users = Dir.entries((charinfo_location + "/" + charinfo))
		@charinfo_all_users.concat(charinfo_users)
		charinfo_users.each { |charinfo_user|
			if (charinfo_user != "." && charinfo_user != "..") 
				check_perm((charinfo_location + "/" + charinfo + "/" + charinfo_user), @charinfo_mode)
				if (charsaves_array.include?(charinfo_user) == false) 
					puts charinfo_user + " is not in " + charsave_location + "!" 
				end 
			end 
		}
		end
	if (users_array.include?(charinfo) == false) # By the way, is every user from charinfo in users too?
		puts charinfo + " is not in " + users_location + "!"
	end
}

# Check if every char from charsave can be found somewhere under charinfo
charsaves.each { |charsave|
	if (charsave != "." && charsave != "..")
			check_perm((charsave_location + "/" + charsave), @charsave_mode)
		if (@charinfo_all_users.include?(charsave) == false) 
			puts charsave + " not found anywhere under " + @prefix + "charinfo/*/!"
		end
	end
}

puts "Done!"
