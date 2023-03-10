if [ "$MYSQL" = "mysql:8.0" ];
then
    wget https://repo.mysql.com//mysql-apt-config_0.8.10-1_all.deb
    sudo dpkg -i mysql-apt-config_0.8.10-1_all.deb

    sudo apt-get update -q
    sudo apt-get install -q -y --allow-unauthenticated -o Dpkg::Options::=--force-confnew mysql-server
    sudo systemctl restart mysql
    sudo mysql_upgrade
    mysql --version
fi
