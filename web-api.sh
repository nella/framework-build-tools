#!/bin/bash

##########
# Config #
##########

BUILD_TOOLS_DIR="build-tools"
PACKAGE_VERSION_NAME="2.0dev"
APIGEN_CONFIG="$BUILD_TOOLS_DIR/apigen-web.neon"
APIGEN_TEMPLATE_CONFIG="$BUILD_TOOLS_DIR/apigen-template/config.neon"
REPOSITORY="git@locutus.blueboard.cz:nellafw.org.git"
API_WORK_DIR="server-api"
DATE_NOW=`date +%F`

#########
# Clean #
#########

if [ -d "$API_WORK_DIR" ]
then
	rm -rf $API_WORK_DIR
fi

git clone $REPOSITORY $API_WORK_DIR
cd $API_WORK_DIR
git checkout -b production origin/production
git rm -rf api
cd ..

##########
# Apigen #
##########

apigen -s "NellaFramework-$PACKAGE_VERSION_NAME/sandbox/libs/" -d "$API_WORK_DIR/api" --config "$APIGEN_CONFIG" --template-config "$APIGEN_TEMPLATE_CONFIG"

##########
# Deploy #
##########

cd $API_WORK_DIR
git add api
git commit -m "update $DATE_NOW"
git push origin production
cd ..
