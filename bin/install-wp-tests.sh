#!/usr/bin/env bash

# This script is based on the install-wp-tests.sh script from the WordPress core repository.
# It has been modified to work with MySQLi and to allow for the installation of a specific WordPress version.

# Check for required arguments, script execution method, and root privileges
USAGE_ERROR=0
if [ $# -lt 3 ] || [ "$0" != "$BASH_SOURCE" ]; then
	echo "Arguments: <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	echo ""
	echo "Example: bash install-wp-tests.sh test_db root root localhost latest false"
	echo ""
	echo "Requirements:"
	echo "1. At least 3 arguments: <db-name> <db-user> <db-pass>"
	echo "2. The script must be run with bash, not sourced."
	echo "3. The script must be run as root."
	echo "4. Packages "curl" or "wget", "svn", "mysql" and "mysqladmin" must be installed."
	USAGE_ERROR=1
fi

# Assign arguments to variables
DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

# Set temporary directory
TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}



# Function to download files using curl or wget
download() {
	# Check if curl or wget is available for downloading files
	if command -v curl > /dev/null; then
		echo "Using curl to download $1"
		curl -s "$1" > "$2"
	elif command -v wget > /dev/null; then
		echo "Using wget to download $1"
		wget -nv -O "$2" "$1"
	else
		echo "Error: curl or wget is required to download files."
		echo ""
		return 1
	fi
}



# Determine the WordPress tests tag based on the version
determine_wp_tests_tag() {
	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
		WP_BRANCH=${WP_VERSION%\-*}
		WP_TESTS_TAG="branches/$WP_BRANCH"
	elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
		WP_TESTS_TAG="branches/$WP_VERSION"
	elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
		if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
			# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
			WP_TESTS_TAG="tags/${WP_VERSION%??}"
		else
			WP_TESTS_TAG="tags/$WP_VERSION"
		fi
	elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		WP_TESTS_TAG="trunk"
	else
		# http serves a single offer, whereas https serves multiple. we only want one
		download http://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
		LATEST_VERSION=$( grep -o '"version":"[^"]*' $TMPDIR/wp-latest.json | sed 's/"version":"//')
		if [[ -z "$LATEST_VERSION" ]]; then
			echo "Latest WordPress version could not be found"
			echo ""
			return 1
		fi
		WP_TESTS_TAG="tags/$LATEST_VERSION"
	fi
}



# Function to install WordPress
install_wp() {
	# Check if the WordPress core directory exists
	if [ -d $WP_CORE_DIR ]; then
		# Check if WordPress is already installed
		if [ -f $WP_CORE_DIR/wp-load.php ]; then
			echo "WordPress core directory already exists and seems to be installed. Skipping..."
			echo ""
			return
		else
			echo "WordPress core directory exists but installation seems incomplete. Cleaning up..."
			echo ""
			rm -rf $WP_CORE_DIR
		fi
	fi

	# Maybe create the WordPress core directory
	if [ ! -d $WP_CORE_DIR ]; then
		echo "Creating WordPress core directory..."
		mkdir -p $WP_CORE_DIR
	fi

	# Download and extract WordPress
	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p $TMPDIR/wordpress-trunk
		rm -rf $TMPDIR/wordpress-trunk/*
		svn export --quiet https://core.svn.wordpress.org/trunk $TMPDIR/wordpress-trunk/wordpress
		mv $TMPDIR/wordpress-trunk/wordpress/* $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
			# https serves multiple offers, whereas http serves single.
			download https://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
			if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
				# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
				LATEST_VERSION=${WP_VERSION%??}
			else
				# otherwise, scan the releases and get the most up to date minor version of the major release
				local VERSION_ESCAPED=`echo $WP_VERSION | sed 's/\./\\\\./g'`
				LATEST_VERSION=$(grep -o '"version":"'$VERSION_ESCAPED'[^"]*' $TMPDIR/wp-latest.json | sed 's/"version":"//' | head -1)
			fi
			if [[ -z "$LATEST_VERSION" ]]; then
				local ARCHIVE_NAME="wordpress-$WP_VERSION"
			else
				local ARCHIVE_NAME="wordpress-$LATEST_VERSION"
			fi
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		
		echo ""
		echo "Downloading WordPress version $ARCHIVE_NAME"
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz $TMPDIR/wordpress.tar.gz
		echo ""
		echo "Extracting WordPress version $ARCHIVE_NAME"
		tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR

		# Check if the WordPress core directory exists
		if [ -d $WP_CORE_DIR ]; then
			echo "WordPress installed"
			echo ""
			echo "Downloading db.php for MySQLi support..."
			download https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
			echo "Done."
		else
			echo "Error: WordPress core directory does not exist. Installation failed."
			echo ""
		fi
	fi
}



# Function to install the test suite
install_test_suite() {
	# Set the option for the sed command based on the operating system
	local ioption
	if [[ $(uname -s) == 'Darwin' ]]; then
		ioption='-i.bak'
	else
		ioption='-i'
	fi
	
	# Check if the test suite directory exists
	if [ -d $WP_TESTS_DIR ]; then
		# Check if the test suite is already installed
		if [ -f $WP_TESTS_DIR/includes/functions.php ]; then
			echo "Test suite directory already exists and seems to be installed. Exiting..."
			echo ""
			return
		else
			echo "Test suite directory exists but installation seems incomplete. Reinstalling..."
			echo ""
			rm -rf $WP_TESTS_DIR
		fi
	fi

	# Create the test suite directory
	mkdir -p $WP_TESTS_DIR
	# Export the test suite from the WordPress develop repository
	rm -rf $WP_TESTS_DIR/{includes,data}
	svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
	svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
	
	# Download the test suite configuration file
	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		# Modify the test suite configuration file
		WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi
}



# Function to recreate the database
recreate_db() {
	# Check if the database exists
	shopt -s nocasematch
	if [[ $1 =~ ^(y|yes)$ ]]; then
		mysqladmin drop $DB_NAME -f --user="$DB_USER" --password="$DB_PASS"$EXTRA
		create_db
		echo "Recreated the database ($DB_NAME)."
		echo ""
	else
		echo "Leaving the existing database ($DB_NAME) in place."
		echo ""
	fi
	shopt -u nocasematch
}



# Function to create the database
create_db() {
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}



# Function to install the database
install_db() {
	# Check if the database creation should be skipped
	if [ ${SKIP_DB_CREATE} = "true" ]; then
		return 0
	fi
	# Extract the host and port from the DB_HOST variable
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]}
	local DB_SOCK_OR_PORT=${PARTS[1]}
	local EXTRA=""
	# Set the host and port or socket
	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			# NOTE: Protocol removed from the command because it is not supported by the LocalWP environment
			# EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_SOCK_OR_PORT ]; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			# NOTE: Protocol removed from the command because it is not supported by the LocalWP environment
			# EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
			EXTRA=" --host=$DB_HOSTNAME"
		fi
	fi
	# Check if the database exists
	if mysql --user="$DB_USER" --password="$DB_PASS"$EXTRA --execute='show databases;' | grep -q ^$DB_NAME$; then
		echo "Reinstalling will delete the existing test database ($DB_NAME)"
		echo ""
		read -p 'Are you sure you want to proceed? [y/N]: ' DELETE_EXISTING_DB
		recreate_db $DELETE_EXISTING_DB
	else
		echo "Creating the database ($DB_NAME)"
		create_db
	fi

	echo "Database installed"
}


# Continue if no usage error
if [ $USAGE_ERROR -eq 0 ]; then
	# Main script execution
	determine_wp_tests_tag
	install_wp
	install_test_suite
	install_db
fi
