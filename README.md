# mariabak

**mariabak** is a command-line frontend program for `mysqldump`, created to facilitate some backup operations on MariaDB and MySQL databases.

All features were created because to achieve the same result using `mysqldump` directly, the user needs to run more than one command. `mariabak` was developed to perform all of them in a single command.

- One dump file for each database (`mysqldump` put all databases into one single dump file)
- You can opt-out dump data from specific tables. The tables structure will be preserved (`mysqldump –-ignore-table` doesn't keep the table structure).
- Select which databases to backup, or all using the * wildcard.
- One directory with a timestamp will be created for each backup operation. This directory is created inside the same directory the command was executed.
- It backups all the user grants, system variables and events to a separate file.

Do you have a comment or a question? [Post it on GitHub Discussions](https://github.com/llagerlof/mariabak/discussions).

## Hands-on

This presentation assume `install.sh` was used for the installation. If you want to use `mariabak.php` directly, change `mariabak` to `php mariabak.php`.

![mariabak](https://user-images.githubusercontent.com/193798/184143085-23380ab9-03da-4d66-8ba8-1aaa1650f3ea.gif)

## Minimum requirements

- **PHP 7**+ on PATH with **pdo_mysql** extension enabled.
- **mysqldump** on PATH (it's part of MariaDB or MySQL client).
- You must have credentials (_username and password_) with enough privileges to perform a backup from selected server.

## Install

You have two options:

- <sup>(Linux)</sup> Use the installer. [Download](https://github.com/llagerlof/mariabak/archive/refs/heads/master.zip) or clone this repository and execute the `install.sh` (run as normal user. if necessary sudo password will be asked).

- <sup>(Linux/Windows)</sup> Download the latest version of standalone script [mariabak.php](https://raw.githubusercontent.com/llagerlof/mariabak/master/mariabak.php) (right click, save link as)

Note: The installer will copy and rename `mariabak.php` to `/usr/bin/mariabak` and make it executable, so after install you just need to type `mariabak` from anywhere to use it.

## Quick start

**List databases:**

If you used the installer:

```shell
$ mariabak -list     # default localhost, user root, empty password
```

If you prefer to use the .php directly:

```shell
$ php mariabak.php -list
```

**Backup all databases. A directory will be created in current directory:**

```shell
$ mariabak --databases=*
```

**Backup one database, asking for the server password interactively:**

```shell
$ mariabak --databases=db1 --host=localhost --user=root -p
```

**Backup some databases, ignore some tables data but preserve its structure:**

```shell
$ mariabak --databases=db1,db2,db3 --ignore-tables=db2.table1,db2.table2,db3.table_a --user=root -p
```

**Backup one database, passing the server password inline:**

```shell
$ mariabak --databases=db1 --host=localhost --user=root --port=3306 --password=hunter2  # Caution with this one. The shell can save command history.
```

## Configuration file (optional)

When using the `install.sh`, a configuration file called `.mariabak.conf` will be copied to the user home directory. You can edit it to change the name format of backup directory.

The default format is `backup-db_{date}_{time}_{host}_{port}`

## Options documentation

**Conventions:**

- All options that require a parameter start with two dashes.
- All options that NOT require a parameter start with one dash.

**Options:**

`--databases` : Comma separated databases to backup. Use * for all.

```shell
$ mariabak --databases=*
$ mariabak --databases=mydatabase,otherdatabase,db3
```

`--ignore-tables` : Comma separated tables to NOT backup data. The table structure will be preserved.

```shell
$ mariabak --ignore-tables=mydatabase.log,mydatabase.photos,db3.cache --databases=mydatabase,otherdatabase,db3
```

`-list` : Just list all available databases. Doesn't perform a backup.

```shell
$ mariabak -list
```

`--host` : The server IP or hostname. If ommited assume "localhost".

```shell
$ mariabak --host=localhost -list
```

`--port` : The server port. If ommited assume "3306".

```shell
$ mariabak --port=3306 --databases=mydb
```

`--user` : The user that will connect to server. If ommited assume "root".

```shell
$ mariabak --user=root --databases=db1,db2
```

`--password` : The server password. If ommited assume empty string.

```shell
$ mariabak --password=hunter2 --databases=* # CAUTION, the shell can save command-line history
```

`-p` : Ask user to type the password.

```shell
$ mariabak -p --databases=*
```
