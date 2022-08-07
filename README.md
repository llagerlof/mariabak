# mariabackup

**mariabackup** is a command-line program in one file, created to facilitate some backup operations on MariaDB and MySQL databases using mysqldump, such as:

- One dump file for each database.
- You can opt-out dump data from specific tables. The tables structure will be preserved.
- Select which databases to backup, or all using the * wildcard.
- One directory with a timestamp will be created for each backup operation. This directory is created inside the same directory the command was executed.
- It backups all the user grants, system variables and events to a separate file.

## Hands-on

![mariabackup](https://user-images.githubusercontent.com/193798/183129717-5ef88ce9-adbd-4a86-bba0-63b8da8365a8.gif)

## Minimum requirements

- **PHP 7**+ on PATH with **pdo_mysql** extension enabled.
- **mysqldump** on PATH (it's part of MariaDB or MySQL client).
- You must have credentials (username and password) with enough privileges to perform a backup from selected server.

## Install

You have two options:

- <sup>(Linux/Windows)</sup> Download the standalone script [mariabackup.php](https://raw.githubusercontent.com/llagerlof/mariabackup/master/mariabackup.php) 1.0.1 (right click, save link as)

- <sup>(Linux)</sup> Use the installer. [Download](https://github.com/llagerlof/mariabackup/archive/refs/heads/master.zip) or clone this repository and execute the **install.sh**

Note: The installer will copy and rename `mariabackup.php` to `/usr/bin/mariabackup` and make it executable, so after the installation you can just type `mariabackup` from anywhere to use it.

## Quick start

**List databases:**

  ```shell
  $ php mariabackup.php -list  # default localhost, user root, empty password
  ```

**Backup all databases. A directory will be created in current directory:**

```shell
$ php mariabackup.php --databases=*
```

**Backup one database, asking for the server password interactively:**

```shell
$ php mariabackup.php --databases=db1 --host=localhost --user=root -p
```

**Backup some databases, ignore some tables data but preserve its structure:**

```shell
$ php mariabackup.php --databases=db1,db2,db3 --ignore-tables=db2.table1,db2.table2,db3.table_a --user=root -p
```

**Backup one database, passing the server password inline:**

```shell
$ php mariabackup.php --databases=db1 --host=localhost --user=root --port=3306 --password=hunter2  # Caution with this one. The shell can save command history.
```

## Options documentation

**Conventions:**

- All options that require a parameter start with two dashes.
- All options that NOT require a parameter start with one dash.

**Options:**

`--databases` : Comma separated databases to backup. Use * for all.

```shell
$ php mariabackup.php --databases=*
$ php mariabackup.php --databases=mydatabase,otherdatabase,db3
```

`--ignore-tables` : Comma separated tables to NOT backup data. The table structure will be preserved.

```shell
$ php mariabackup.php --ignore-tables=mydatabase.log,mydatabase.photos,db3.cache --databases=mydatabase,otherdatabase,db3
```

`-list` : Just list all available databases. Doesn't perform a backup.

```shell
$ php mariabackup.php -list
```

`--host` : The server IP or hostname. If ommited assume "localhost".

```shell
$ php mariabackup.php --host=localhost -list
```

`--port` : The server port. If ommited assume "3306".

```shell
$ php mariabackup.php --port=3306 --databases=mydb
```

`--user` : The user that will connect to server. If ommited assume "root".

```shell
$ php mariabackup.php --user=root --databases=db1,db2
```

`--password` : The server password. If ommited assume empty string.

```shell
$ php mariabackup.php --password=hunter2 --databases=* # CAUTION, the shell can save command-line history
```

`-p` : Ask user to type the password.

```shell
$ php mariabackup.php -p --databases=*
```
