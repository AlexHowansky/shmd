#!/bin/bash

# This script will traverse all raw files in the staging directory,
# rename the extensions to lowercase, and then drop resized copies
# into the public directory.

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
STAGE_DIR=${SCRIPT_DIR}/../staging
PHOTO_DIR=${SCRIPT_DIR}/../public/photos

umask 0022

cd ${STAGE_DIR} || exit

for DIR in $(find . -mindepth 1 -maxdepth 1 -type d)
do

    GALLERY=$(echo ${DIR} | cut -d'/' -f2)
    cd ${STAGE_DIR}/${GALLERY} || exit
    echo ${GALLERY}

    echo "  renaming photos"
    rename .JPG .jpg *.JPG 2>/dev/null
    rename .JPEG .jpg *.JPEG 2>/dev/null
    rename .jpeg .jpg *.jpeg 2>/dev/null

    if [ ! -d ${PHOTO_DIR}/${GALLERY} ]
    then
        echo "  making new public dir"
        mkdir -p ${PHOTO_DIR}/${GALLERY}
    fi

    SAVEIFS=$IFS
    IFS=$(echo -en "\n\b")
    for FILE in $(find . -mindepth 1 -maxdepth 1 -type f -name "*.jpg")
    do
        PHOTO=$(basename "${FILE}")
        echo -n "    ${PHOTO}"
        if [ -f "${PHOTO_DIR}/${GALLERY}/${PHOTO}" ]
        then
            echo " skipped"
        else
            convert -resize 600x600 ${FILE} "${PHOTO_DIR}/${GALLERY}/${PHOTO}"
            echo " resized"
        fi
    done
    IFS=$SAVEIFS

done
