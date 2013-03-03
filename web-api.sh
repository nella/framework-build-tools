#!/bin/bash

##########
# Config #
##########

BUILD_TOOLS_DIR="build-tools"
PACKAGE_VERSION_NAME="2.0dev"
APIGEN_CONFIG="$BUILD_TOOLS_DIR/apigen-web.neon"
APIGEN_TEMPLATE_CONFIG="$BUILD_TOOLS_DIR/apigen-template/config.neon"
BUCKET="s3://api.nellafw.org/"
API_WORK_DIR="server-api"
DATE_NOW=`date +%F`

#########
# Clean #
#########

if [ -d "$API_WORK_DIR" ]
then
	rm -rf $API_WORK_DIR
fi

##########
# Apigen #
##########

apigen -s "NellaFramework-$PACKAGE_VERSION_NAME/sandbox/libs/" -d "$API_WORK_DIR/api" --config "$APIGEN_CONFIG" --template-config "$APIGEN_TEMPLATE_CONFIG"

#########
# Clean #
#########

wget http://api.nellafw.org/404.html -O "$API_WORK_DIR/api/404.html"
s3cmd del -rf "$BUCKET"

##########
# Deploy #
##########

s3cmd put -rP "$API_WORK_DIR" "$BUCKET"
