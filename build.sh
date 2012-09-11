#!/bin/bash

##########
# Config #
##########

BUILD_TOOLS_DIR="build-tools"
PACKAGE_VERSION_NAME="2.0dev"
PACKAGE_NAME="NellaFramework-$PACKAGE_VERSION_NAME"
APIGEN_CONFIG="$BUILD_TOOLS_DIR/apigen-package.neon"
APIGEN_TEMPLATE_CONFIG="$BUILD_TOOLS_DIR/apigen-template/config.neon"
DATE_NOW=`date +%F`

#############################################
# GIT commit build (hash, date) - history.txt
#############################################

WCREV=`git log -n 1 --pretty="%h"`
echo `git log -n 500 --pretty="%cd (%h): %s" --date-order --date=short > history.txt`

#################
# Build sandbox #
#################

git clone https://github.com/nella/framework-sandbox.git sandbox
cp -R Nella sandbox/libs/Nella
cp -R vendor/nette/nette/Nette sandbox/libs/Nette
cp -R vendor/doctrine/common/lib/Doctrine sandbox/libs/Doctrine
cp -R vendor/doctrine/dbal/lib/Doctrine sandbox/libs/Doctrine
cp -R vendor/doctrine/orm/lib/Doctrine sandbox/libs/Doctrine
cp -R vendor/doctrine/migrations/lib/Doctrine sandbox/libs/Doctrine
cp -R vendor/symfony/console/Symfony sandbox/libs/Symfony

#########################
# Nette Framework Tools #
#########################

git clone git://github.com/nella/framework-tools.git tools

##########################################
# GIT remove .gitignore .gitmodules .git #
##########################################

find . -name ".git*" -print0 | xargs -0 rm -rf

##########
# Apigen #
##########

apigen -s "sandbox/libs" -d "API-reference" --config "$APIGEN_CONFIG" --template-config "$APIGEN_TEMPLATE_CONFIG"

#########
# Clean #
#########

rm -rf "vendor"

############
# Packages #
############

# Prepare
mkdir "$PACKAGE_NAME"
mv "Nella" "$PACKAGE_NAME/"
mv "sandbox" "$PACKAGE_NAME/"
mv "tools" "$PACKAGE_NAME/"
mv "license.md" "$PACKAGE_NAME/"
mv "history.txt" "$PACKAGE_NAME/"
mv "readme.md" "$PACKAGE_NAME/"
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
cp "$PACKAGE_NAME.7z" "$PACKAGE_NAME-$DATE_NOW-$WCREV.7z"
