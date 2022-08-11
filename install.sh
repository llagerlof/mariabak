#!/bin/bash

MARIABAK_PATH="$(dirname $0)/mariabak.php"
MINIMUM_REQUIREMENTS="MINIMUM REQUIREMENTS (both must be on PATH):\n  - PHP 7+\n  - mysqldump (it's part of MariaDB/MySQL client)\n"

echo -e "\n> Installer for mariabak 1.2.0\n"

# Test if php is on path
if ! command -v php &> /dev/null ;then
    echo -e "ERROR: php executable not found on PATH. Installation cancelled.\n";
    echo -e $MINIMUM_REQUIREMENTS
    exit
fi

# Test if mysqldump is on path
if ! command -v mysqldump &> /dev/null ;then
    echo -e "ERROR: mysqldump executable not found on PATH. Installation cancelled.\n"
    echo -e $MINIMUM_REQUIREMENTS
    exit
fi

# Ask for user confirmation
echo -e "This script will install mariabak on /usr/bin and make it executable.\n"
echo -e "Proceed (y/N)?"

read a

if [[ ! "$a" =~ ^(y|Y) ]] ;then
    echo -e "Installation cancelled."
    exit
fi

if test -f "/usr/bin/mariabak"; then
    echo -e "The file /usr/bin/mariabak already exist.\n"
    echo -e "Overwrite (y/N)?"

    read a

    if [[ ! "$a" =~ ^(y|Y) ]] ;then
        echo -e "Installation cancelled."
        exit
    fi
fi

# Try to copy the script, removing its extension
sudo cp $MARIABAK_PATH /usr/bin/mariabak

if [ $? -ne 0 ]; then
   echo -e "ERROR: Could not install mariabak in /usr/bin\n"
   exit
fi

# Try to make the copied file executable
sudo chmod +x /usr/bin/mariabak

if [ $? -ne 0 ]; then
   echo -e "ERROR: Could not set to executable the script /usr/bin/mariabak\n"
   exit
fi

echo -e "\nSUCCESS! mariabak installed.\n"
echo -e "Try it by typing \"mariabak\". A help will be shown.\n"
