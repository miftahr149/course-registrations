#!/bin/bash
# Runs once, when the MySQL volume is created for the first time.
# Creates a separate database for automated tests, so tests (which wipe tables)
# never touch the development data in ${MYSQL_DATABASE}.
set -e

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_testing\`;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_testing\`.* TO '${MYSQL_USER}'@'%';
SQL
