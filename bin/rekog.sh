#!/bin/bash

# This script will make copies of the staging photos, and
# reduce them in size until they're under 5Mb, which is
# the cap for the Rekognition API. These resized photos
# can then be used with the identifyPhotos.php script.

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
STAGE_DIR=${SCRIPT_DIR}/../staging
REKOG_DIR=${SCRIPT_DIR}/../rekog

umask 0022

cd ${STAGE_DIR} || exit

for DIR in $(find . -mindepth 1 -maxdepth 1 -type d)
do

    GALLERY=$(echo ${DIR} | cut -d'/' -f2)
    cd ${STAGE_DIR}/${GALLERY} || exit
    echo ${GALLERY}

    if [ ! -d ${REKOG_DIR}/${GALLERY} ]
    then
        echo "  making new rekog dir"
        mkdir -p ${REKOG_DIR}/${GALLERY}
    fi

    SAVEIFS=$IFS
    IFS=$(echo -en "\n\b")
    for SOURCE in $(find . -mindepth 1 -maxdepth 1 -type f -name "*.jpg")
    do
        PHOTO=$(basename "${SOURCE}")
        echo -n "    ${PHOTO}"
        FILE="${REKOG_DIR}/${GALLERY}/${PHOTO}"
        [ -f ${FILE} ] || cp ${SOURCE} ${FILE}
        SIZE=$(stat --printf="%s" "${FILE}")
        while [ ${SIZE} -gt 5000000 ]
        do
            echo -n " ${SIZE}"
            mogrify -resize 75x75% "${FILE}"
            SIZE=$(stat --printf="%s" "${FILE}")
        done
        echo " ${SIZE}"
    done
    IFS=$SAVEIFS

done
