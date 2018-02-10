#!/bin/bash

# This script will traverse all raw files in the staging directory, rename
# the extensions to lowercase, and then drop resized copies into the public
# directory. It can be run at any time -- it will process only new photos.
# It will also create resized copies in the rekog directory -- these are
# required because the Rekognition API caps image size at 5Mb.

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
STAGE_DIR="${SCRIPT_DIR}/../staging"
PHOTO_DIR="${SCRIPT_DIR}/../public/photos"
REKOG_DIR="${SCRIPT_DIR}/../rekog"

umask 0022

cd "${STAGE_DIR}" || exit

for DIR in $(find . -mindepth 1 -maxdepth 1 -type d)
do

    GALLERY=$(echo ${DIR} | cut -d'/' -f2)
    cd "${STAGE_DIR}/${GALLERY}" || exit
    echo ${GALLERY}

    echo "  renaming photos"
    rename .JPG .jpg *.JPG 2>/dev/null
    rename .JPEG .jpg *.JPEG 2>/dev/null
    rename .jpeg .jpg *.jpeg 2>/dev/null

    if [ ! -d "${PHOTO_DIR}/${GALLERY}" ]
    then
        echo "  making new public dir"
        mkdir -p "${PHOTO_DIR}/${GALLERY}"
    fi

    if [ ! -d "${REKOG_DIR}/${GALLERY}" ]
    then
        echo "  making new Rekognition API dir"
        mkdir -p "${REKOG_DIR}/${GALLERY}"
    fi

    SAVEIFS=$IFS
    IFS=$(echo -en "\n\b")
    for FILE in $(find . -mindepth 1 -maxdepth 1 -type f -name "*.jpg")
    do
        PHOTO=$(basename "${FILE}")

        echo -n "    ${PHOTO} "
        if [ -f "${PHOTO_DIR}/${GALLERY}/${PHOTO}" ]
        then
            echo "skipped public resize"
        else
            convert -resize 600x600 ${FILE} "${PHOTO_DIR}/${GALLERY}/${PHOTO}"
            echo "resized for public"
        fi

        echo -n "    ${PHOTO} "
        if [ -f "${REKOG_DIR}/${GALLERY}/${PHOTO}" ]
        then
            echo "skipped Rekognition API resize"
        else
            cp ${FILE} "${REKOG_DIR}/${GALLERY}/${PHOTO}"
            while [ $(stat --printf="%s" "${REKOG_DIR}/${GALLERY}/${PHOTO}") -gt 5000000 ]
            do
                echo -n "."
                mogrify -resize 80x80% "${REKOG_DIR}/${GALLERY}/${PHOTO}"
            done
            echo " resized for Rekognition API"
        fi

    done
    IFS=$SAVEIFS

done
