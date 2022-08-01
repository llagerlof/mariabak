# mariabackup

**mariabackup** it's a command-line program in one file, created to facilitate some backup operations on MariaDB and MySQL databases using mysqldump, such as:

- One dump file for each database.
- You can opt-out dump data from specific tables. The tables structure will be preserved.
- Select which databases to backup, or all using the * wildcard.
- One directory with a timestamp will be created for each backup operation. This directory is created inside the same directory the command was executed.
- As a plus, it backups all the user grants and system variables to a separate file.

## Minimum requirements

- **PHP 7**+ with **pdo_mysql** extension enabled.
- You must have the credentials (username and password) with enough privileges to perform a backup on selected server.

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
