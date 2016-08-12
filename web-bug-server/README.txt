Web Bug Server consists of a php page that takes requests in a specific format, logs details about the requests to a database, and returns the type of media file requested.

By default, Web Bug Server uses a MySQL database.  To set up the database structure for Web Bug Server run the following MySQL commnds.
# Begin SQL commands
CREATE DATABASE webbug;
USE webbug;
CREATE TABLE requests (id TEXT, type TEXT, ip_address TEXT, user_agent TEXT, time INTEGER);
GRANT USAGE ON *.* TO webbuguser@localhost IDENTIFIED BY 'adhd';
GRANT ALL PRIVILEGES ON webbug.* TO webbuguser@localhost;
# End SQL commands

These commands assume that the MySQL database is running on the same machine as Web Bug Server and sets up a database called 'webbug' with a 'requests' table and gives access to 'webbuguser' with a password of 'adhd'.
