#!/bin/bash -e

# THIS IS NOT PRODUCTION SETTINGS
# THEY ARE CONTAINS NOT SECURED SOLUTIONS
# MYSQL WILL BE ACCESSIBLE FROM ANY HOST
# USE IT ONLY FOR THE LOCAL DEVELOPMENT

# let's install latest versions of software
sudo add-apt-repository ppa:ondrej/php -y
sudo add-apt-repository ppa:nginx/stable -y
sudo apt-key adv --keyserver ha.pool.sks-keyservers.net --recv-keys 5072E1F5
sudo echo "deb http://repo.mysql.com/apt/ubuntu/ trusty mysql-5.7" | sudo tee -a /etc/apt/sources.list.d/mysql.list

sudo apt-get update

sudo apt-get -y install php7.0
sudo apt-get -y install php7.0-mysql
sudo apt-get -y install php7.0-fpm

sudo apt-get -y install nginx

MYSQL_ROOT_PASS="root"
MYSQL_USER="statboard"
MYSQL_PASSWORD="statboard"
sudo debconf-set-selections <<< "mysql-community-server mysql-community-server/data-dir select ''"
sudo debconf-set-selections <<< "mysql-community-server mysql-community-server/root-pass password $MYSQL_ROOT_PASS"
sudo debconf-set-selections <<< "mysql-community-server mysql-community-server/re-root-pass password $MYSQL_ROOT_PASS"
sudo apt-get install -y mysql-server

sudo cp /vagrant/bin/configs/nginx_vagrant.conf /etc/nginx/sites-available/default
sudo service nginx restart

# create an user and database
mysql -uroot -p${MYSQL_ROOT_PASS} -e "CREATE USER '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';"
mysql -uroot -p${MYSQL_ROOT_PASS} -e "CREATE DATABASE statboard CHARACTER SET utf8 COLLATE utf8_general_ci;"
mysql -uroot -p${MYSQL_ROOT_PASS} -e "GRANT ALL ON statboard.* TO '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';"
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} statboard < /vagrant/examples/db-example.sql

# allow to connect from the any host
sudo sed -i 's/bind-address/#bind-address/' /etc/mysql/my.cnf
sudo service mysql restart

# go to the project directory after ssh login
sh -c "echo 'cd /vagrant/' >> /home/vagrant/.profile"