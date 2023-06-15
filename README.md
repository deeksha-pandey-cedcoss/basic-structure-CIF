## Prerequisites before docker environment and project setup:-

Stop/Uninstall the below services/software's if they exists in your base system.
  1. lamp
  2. apache
  3. nginx   
  4. mongo
  5. redis

## Step 1:- Docker and Docker-compose Installation

- #### Docker installation
1. sudo apt update
2. sudo apt install apt-transport-https ca-certificates curl software-properties-common
3. curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
4. sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"
5. sudo apt install docker-ce

- #### docker-compose installation

1. sudo curl -L "https://github.com/docker/compose/releases/download/1.25.0/docker-compose-$(uname
-s)-$(uname -m)" -o /usr/local/bin/docker-compose
2. sudo chmod +x /usr/local/bin/docker-compose
3. sudo chown -R $USER:$USER /var/run/docker.sock
4.  sudo systemctl restart docker.service

**Note-** You can also visit https://www.docker.com/get-started for more information or another installation method.

## Step 2:- Get docker environment project in your system:

 ***Clone the master branch of the docker-cedcommerce-integration-framework repository by using the below command :-***

      
    git clone git@github.com:cedcommerce/docker-cedcommerce-integration-framework.git

## Step 3:- Build and Run Docker Project :-

**Enter below command, it will build and run all docker containers :-**

	docker-compose up
	 or 
	docker-compose up --build

**Note -** Use all commands from root directory of your docker project (docker-cedcommerce-integration-framework/). It will take up-to 15 minutes (depends on your system performance and internet speed).

## Step 4:- Add domains in hosts file :-

**Add below text in your hosts file(you can find it at /etc/hosts) :-**

    127.0.0.1 remote.local.cedcommerce.com home.local.cedcommerce.com
 
## Step 5:- Make ready your all projects :-

**A. Update config files of your apps accordingly**

**B. Update all projects**
	
     bin/update
It will run cache flush and setup upgrade install command in back end apps. You can customise update script for other commands according to your need. 

## Step 6:- Test all domains in web-browser :-

**A. Back-end apps (home and remote app):-**

	home.local.cedcommerce.com
	remote.local.cedcommerce.com
 
**B. SQS :-**

	http://127.0.0.1:9325


## => Some useful commands for docker environment

#### 1. Start/Stop/restart Docker Containers
- Start 
  ```
  bin/start
  ```
  or
  ```
  docker-compose start 
  ```

- Stop
  ```
  bin/stop
  ```
  or
  ```
  docker-compose stop
  ```
- Restart
  ```
  bin/restart
  ```
  
#### 2. Import/Restore database

It will Import/restore all databases (home, remote) from dump files (from phalcon-docker/backup) in your mongo containers.
```
bin/restore
```

#### 3. Export/backup database

It will export database dump files (inside backup folder).
```
bin/backup
```

#### 4. Update all apps(default only for back-end apps )

It will run cache flush and setup upgrade install command in back end apps. You can customise update script for other commands according to your need.   
```
bin/update
```

## => Some Useful commands to manage docker

 -  To check list of all docker images.
  `sudo docker images`
 
 - To check docker existing image and container details 
  `sudo docker ps -a` 
    or
  `sudo docker ps -all` 
    
 - Start/Stop/Kill  container 	
	`sudo docker start container_id`
	`sudo docker stop container_id`
	`sudo docker kill container_id` 

	**Example:-** `sudo docker start 81ffcb3a4050` 
	
 - Visit inside a running container
 -  
   **Syntax:-**
		`sudo docker-compose exec container_name bash`
		
   **Example:-** 
		`sudo docker-compose exec node bash`
	
    **Note:-** Inside docker container you can perform all the operations that you can perform in your real linux system.
	
	**Ex:-**	
		
		1. service nginx status
		 
		2. cat /abc/xyz/abc.txt
		
		3. apt-get install snap
		
		4. cd var/www/
		
		5. nano abc.txt etc
		
 - Exit from running container	
	`ctrl+d`
-  Check Memory and CPU Utilization of docker containers 	
	`docker stats`
 	`docker stats --no-stream`

# Create Modules (for phalcon app)
Follow the below link for the modules setup  in phalcon app :
[./module.md](./module.md)
