#!/bin/bash

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
STAGE_DIR=${SCRIPT_DIR}/../staging
PHOTO_DIR=${SCRIPT_DIR}/../public/photos

cd ${STAGE_DIR} || exit
for FILE in $(find . -mindepth 2 -maxdepth 2 -type f -name "*.jpg")
do
    GALLERY=$(echo ${FILE} | cut -d'/' -f2)
    PHOTO=$(basename ${FILE})
    if [ ! -d ${PHOTO_DIR}/${GALLERY} ]
    then
        mkdir -p ${PHOTO_DIR}/${GALLERY}
    fi
    if [ -f ${PHOTO_DIR}/${GALLERY}/${PHOTO} ]
    then
        echo "${GALLERY}/${PHOTO} ... skipped"
    else
        echo "${GALLERY}/${PHOTO} ... resizing"
        convert -resize 600x600 ${FILE} ${PHOTO_DIR}/${GALLERY}/${PHOTO}
    fi
done
