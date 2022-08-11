# mariabak

**mariabak** is a command-line program in one file, created to facilitate some backup operations on MariaDB and MySQL databases using mysqldump, such as:

- One dump file for each database.
- You can opt-out dump data from specific tables. The tables structure will be preserved.
- Select which databases to backup, or all using the * wildcard.
- One directory with a timestamp will be created for each backup operation. This directory is created inside the same directory the command was executed.
- It backups all the user grants, system variables and events to a separate file.

## Minimum requirements

- **PHP 7**+ on PATH with **pdo_mysql** extension enabled.
- **mysqldump** on PATH (it's part of MariaDB or MySQL client).
- You must have credentials (_username and password_) with enough privileges to perform a backup from selected server.

## Install

You have two options:

- <sup>(Linux/Windows)</sup> Download the standalone script [mariabak.php](https://raw.githubusercontent.com/llagerlof/mariabak/master/mariabak.php) 1.2.0 (right click, save link as)

- <sup>(Linux)</sup> Use the installer. [Download](https://github.com/llagerlof/mariabak/archive/refs/heads/master.zip) or clone this repository and execute the `install.sh`

Note: The installer will copy and rename `mariabak.php` to `/usr/bin/mariabak` and make it executable, so you just need to type `mariabak` from anywhere to use it.

## Quick start

**List databases:**

  ```shell
  $ php mariabak.php -list  # default localhost, user root, empty password
  ```

**Backup all databases. A directory will be created in current directory:**

```shell
$ php mariabak.php --databases=*
```

**Backup one database, asking for the server password interactively:**

```shell
$ php mariabak.php --databases=db1 --host=localhost --user=root -p
```

**Backup some databases, ignore some tables data but preserve its structure:**

```shell
$ php mariabak.php --databases=db1,db2,db3 --ignore-tables=db2.table1,db2.table2,db3.table_a --user=root -p
```

**Backup one database, passing the server password inline:**

```shell
$ php mariabak.php --databases=db1 --host=localhost --user=root --port=3306 --password=hunter2  # Caution with this one. The shell can save command history.
```

## Options documentation

**Conventions:**

- All options that require a parameter start with two dashes.
- All options that NOT require a parameter start with one dash.

**Options:**

`--databases` : Comma separated databases to backup. Use * for all.

```shell
$ php mariabak.php --databases=*
$ php mariabak.php --databases=mydatabase,otherdatabase,db3
```

`--ignore-tables` : Comma separated tables to NOT backup data. The table structure will be preserved.

```shell
$ php mariabak.php --ignore-tables=mydatabase.log,mydatabase.photos,db3.cache --databases=mydatabase,otherdatabase,db3
```

`-list` : Just list all available databases. Doesn't perform a backup.

```shell
$ php mariabak.php -list
```

`--host` : The server IP or hostname. If ommited assume "localhost".

```shell
$ php mariabak.php --host=localhost -list
```

`--port` : The server port. If ommited assume "3306".

```shell
$ php mariabak.php --port=3306 --databases=mydb
```

`--user` : The user that will connect to server. If ommited assume "root".

```shell
$ php mariabak.php --user=root --databases=db1,db2
```

`--password` : The server password. If ommited assume empty string.

```shell
$ php mariabak.php --password=hunter2 --databases=* # CAUTION, the shell can save command-line history
```

`-p` : Ask user to type the password.

```shell
$ php mariabak.php -p --databases=*
```
