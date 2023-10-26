#!/bin/bash
# load the env variables
source /usr/env/files/html/env.sh
# backup the DB first
mysqldump -u"$DB_USER" -p"$DB_PASS" nfl_data > nfl_data.sql
echo "DB backup complete..."

# commit the changes to git
echo "Enter your commit message:"
read message

git add .
git commit -m "$message"
git push
