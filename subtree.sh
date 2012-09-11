#!/bin/bash

##########
# Config #
##########

REPOSITORY="https://github.com/nella/framework.git"
SUBTREE_WORK_DIR="src"

function split {
	git remote rm $1
	git branch -D $1
	git subtree split -P $2 -b $1
	git remote add $1 $3
	git push $1 $1:master --force
}

#######
# Run #
#######

if [ -d "$SUBTREE_WORK_DIR" ]
then
	rm -rf $SUBTREE_WORK_DIR
fi

git clone $REPOSITORY $SUBTREE_WORK_DIR
cd $SUBTREE_WORK_DIR

split console "Nella/Console" "git@github.com:nella/console.git"
split doctrine "Nella/Doctrine" "git@github.com:nella/doctrine.git"
split diagnostics "Nella/Diagnostics" "git@github.com:nella/diagnostics.git"
split forms "Nella/Forms" "git@github.com:nella/forms.git"
split media "Nella/Media" "git@github.com:nella/media.git"
split localization "Nella/Localization" "git@github.com:nella/Localization.git"
