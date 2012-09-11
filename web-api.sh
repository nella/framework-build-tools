#!/bin/bash

##########
# Config #
##########

PACKAGE_VERSION_NAME="2.0dev"
APIGEN_CONFIG="build/apigen-web.neon"
APIGEN_TEMPLATE_CONFIG="build/apigen-template/config.neon"
REPOSITORY="git@locutus.blueboard.cz:nellafw.org.git"

#########
# Clean #
#########

if [ -d "repository" ]
then
	rm -rf repository
fi

git clone $REPOSITORY repository
cd repository
git checkout -b production origin/production
git rm -rf api
cd ..

##########
# Apigen #
##########

apigen -s "NellaFramework-$PACKAGE_VERSION_NAME/sandbox/libs/" -d "repository/api" --config "$APIGEN_CONFIG" --template-config "$APIGEN_TEMPLATE_CONFIG"

##########
# Deploy #
##########

DATE_NOW=`date +%F`
cd repository
git add api
git commit -m "update $DATE_NOW"
git push origin production
cd ..
