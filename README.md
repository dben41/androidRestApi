androidRestApi
==============

This is a RESTful server implementation for my Android app. It is written in PHP, and uses MYSQL. I'm hosting it on WAMP. It accepts GET, POST, PUT, and DELETE requests. It sends and accepts responses in JSON.  


	User REST API Interface:  
  GET      /users 					Gets all the users  
  GET      /users/2					Retrieves a user based on primary key  
	POST     /users/login 		Send a username and password and receive a message.  
	POST     /api/users/add   Create a new user  
	PUT      /api/users/2			Updates user based on primary key  
	DELETE   /api/users/2			Deletes user based on primary key  

How to setup:
============
1. Download WAMP onto your local machine.
2. Modify the `httpd.conf` which is located in `wamp/bin/apache/Apache2.4.4/conf`.
    -Append this line to the end: `Alias /users "c:/wamp/www/api/index.php"`  
3. Create a folder and file named `c/wamp/www/api/index.php`
4. Place this code in `index.php`. (And obviously modify it to you needs.)
5. Restart your WAMP server. (Or start it if it's never been started.)
6. Navigate to the URL `http://localhost/quotes` *Note: You may need to try `http://localhost:80/quotes` or `http://localhost:8080/quotes` if your server is configured as such. You may need to reconfigure wamp to accept connections on port 8080 because Skype defaults to 80.*
7. Customize and enjoy! Use Postman to test it out.


