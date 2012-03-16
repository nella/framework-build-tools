#!/bin/bash

##########
# Config #
##########

PACKAGE_VERSION_NAME="2.0dev"

###################################################################################
# GIT commit build (hash, date) - history.txt / version.txt / Nella/Framework.php #
###################################################################################

WCREV=`git log -n 1 --pretty="%h"`
WCDATE=`git log -n 1 --pretty="%cd" --date=short`

echo `git log -n 500 --pretty="%cd (%h): %s" --date-order --date=short > HISTORY.txt`
echo `git log -n 1 --pretty="Nella Framework 2.0-dev (revision %h released on %cd)" --date=short > VERSION.txt`

sed -i "s/\$WCREV\$ /$WCREV /g" Nella/Framework.php
sed -i "s/\$WCDATE\$'/$WCDATE'/g" Nella/Framework.php

#################
# Build sandbox #
#################

git clone git://github.com/nella/framework-sandbox.git sandbox
cp -r vendors/pear-nette/Nette/Nette sandbox/libs/Nette
cp vendors/pear-nette/Nette/license.txt sandbox/libs/Nette/
cp -r vendors/doctrine/common/lib/Doctrine sandbox/libs/Doctrine
cp -r vendors/doctrine/dbal/lib/Doctrine sandbox/libs/Doctrine
cp -r vendors/doctrine/orm/lib/Doctrine sandbox/libs/Doctrine
cp -r vendors/doctrine/migrations/lib/Doctrine sandbox/libs/Doctrine
cp vendors/doctrine/orm/LICENSE sandbox/libs/Doctrine/
cp -r vendors/beberlei/DoctrineExtensions sandbox/libs/DoctrineExtensions
cp -r vendors/symfony/console/Symfony sandbox/libs/Symfony
cp -r Nella/* sandbox/libs/Nella

##########################################
# GIT remove .gitignore .gitmodules .git #
##########################################

find . -name ".git*" -print0 | xargs -0 rm -rf

##########
# Apigen #
##########

APIGEN_CONFIG="build/apigen.neon"
APIGEN_TEMPLATE_CONFIG="build/apigen-template/config.neon"

apigen -s "sandbox/libs" -d "API-reference" --config "$APIGEN_CONFIG" --template-config "$APIGEN_TEMPLATE_CONFIG"

#########
# Clean #
#########

rm -rf "vendors"

############
# Packages #
############

# Prepare
PACKAGE_NAME="NellaFramework-$PACKAGE_VERSION_NAME"
mkdir "$PACKAGE_NAME"
#mv "client-side" "$PACKAGE_NAME/"
mv "Nella" "$PACKAGE_NAME/"
mv "sandbox" "$PACKAGE_NAME/"
mv "LICENSE.txt" "$PACKAGE_NAME/"
mv "VERSION.txt" "$PACKAGE_NAME/"
mv "HISTORY.txt" "$PACKAGE_NAME/"
mv "README.txt" "$PACKAGE_NAME/"
mv "API-reference" "$PACKAGE_NAME/"
mv "tests" "$PACKAGE_NAME/"

# tar.gz
tar cvzf "$PACKAGE_NAME.tar.gz" "$PACKAGE_NAME"

# tar.bz2
tar cvjf "$PACKAGE_NAME.tar.bz2" "$PACKAGE_NAME"

# zip
7z a -mx9 "$PACKAGE_NAME.zip" "$PACKAGE_NAME"

# 7z
7z a -mx9 "$PACKAGE_NAME.7z" "$PACKAGE_NAME"
DATE_NOW=`date +%F`
cp "$PACKAGE_NAME.7z" "$PACKAGE_NAME-$DATE_NOW-$WCREV.7z"
