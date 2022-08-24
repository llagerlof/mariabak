#!/bin/bash

MARIABAK_PATH="$(dirname $0)/mariabak.php"
CONFIG_PATH="$(dirname $0)/.mariabak.conf"
MINIMUM_REQUIREMENTS="MINIMUM REQUIREMENTS (both must be on PATH):\n  - PHP 7+\n  - mysqldump (it's part of MariaDB/MySQL client)\n"

# If mariabak.php doesn't exist in the same directory as this script, exit
if [ ! -f "$MARIABAK_PATH" ]; then
    echo -e "ERROR: mariabak.php not found in the same directory as this script.\n"
    exit 1
fi

# If .mariabak.conf doesn't exist in the same directory as this script, exit
if [ ! -f "$CONFIG_PATH" ]; then
    echo -e "ERROR: .mariabak.conf not found in the same directory as this script.\n"
    exit 1
fi

# Get mariabak.php version
code=$(cat $MARIABAK_PATH)
regex_version="@version[[:space:]]+([[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+)"

if [[ $code =~ $regex_version ]]; then
	version=${BASH_REMATCH[1]}
fi

echo -e "\n> Installer for mariabak $version\n"

# Test if php is on path
if ! command -v php &> /dev/null ;then
    echo -e "ERROR: php executable not found on PATH. Installation cancelled.\n";
    echo -e $MINIMUM_REQUIREMENTS
    exit 1
fi

# Test if mysqldump is on path
if ! command -v mysqldump &> /dev/null ;then
    echo -e "ERROR: mysqldump executable not found on PATH. Installation cancelled.\n"
    echo -e $MINIMUM_REQUIREMENTS
    exit 1
fi

# Ask for user confirmation to install the script
echo -e "This script will install mariabak on /usr/bin and make it executable.\n"
echo -e "Proceed (y/N)?"

read a

if [[ ! "$a" =~ ^(y|Y) ]] ;then
    echo -e "Installation cancelled."
    exit 125
fi

# Ask for user confirmation to overwrite if the script is already installed
if test -f "/usr/bin/mariabak"; then
    echo -e "The file /usr/bin/mariabak already exist.\n"
    echo -e "Overwrite (y/N)?"

    read a

    if [[ ! "$a" =~ ^(y|Y) ]] ;then
        echo -e "Installation cancelled."
        exit 125
    fi
fi

# Ask for user confirmation to overwrite the configuration file
if test -f "$HOME/.mariabak.conf"; then
    echo -e "The configuration file $HOME/.mariabak.conf already exist.\n"
    echo -e "Overwrite (y/N)?"

    read a

    if [[ "$a" =~ ^(y|Y) ]] ;then
        # Try to copy the configuration file
        cp $CONFIG_PATH $HOME/.mariabak.conf

        if [ $? -ne 0 ]; then
            echo -e "ERROR: Could not copy .mariabak.conf to $HOME\n"
            exit 1
        fi
    fi
fi

# Try to copy the script, removing its extension
sudo cp $MARIABAK_PATH /usr/bin/mariabak

if [ $? -ne 0 ]; then
   echo -e "ERROR: Could not copy mariabak to /usr/bin\n"
   exit 1
fi

# Try to make the copied file executable
sudo chmod +x /usr/bin/mariabak

if [ $? -ne 0 ]; then
   echo -e "ERROR: Could not set to executable the script /usr/bin/mariabak\n"
   exit 1
fi

echo -e "\nSUCCESS! mariabak $version installed.\n"
echo -e "Try it by typing \"mariabak\". A help will be shown.\n"
