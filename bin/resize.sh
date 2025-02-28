#!/bin/bash

# This script will traverse all image files in the staging directory, rename
# extensions to lowercase, and then drop appropriately resized copies into the
# public directory. It will also create resized copies in the rekog directory,
# which are required because the Rekognition API caps image size at 5Mb. We
# generally don't even need photos that large however, as the recognition
# still works fine with pretty low resolution.
#
# This script can be run at any time, it will process only new photos.

SIZE=1000000

if [ ! -x "$(type -p rename)" ]
then
    echo "Unable to find rename tool."
    echo "Try running: 'sudo apt install rename'"
    exit 1
fi

if [ -x "$(type -p imgp)" ]
then
    IMGP=1
else
    IMGP=0
    if [ -x "$(type -p convert)" ] && [ -x "$(type -p mogrify)" ]
    then
        echo "Unable to find the preferred resizing tool imgp, falling back to the slower ImageMagick."
        echo "Try running: 'sudo apt install imgp'"
        echo
    else
        echo "Unable to find a resizing tool."
        echo "Try running: 'sudo apt install imgp' or 'sudo apt install imagemagick'"
        exit 1
    fi
fi

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
STAGE_DIR="${SCRIPT_DIR}/../staging"
PHOTO_DIR="${SCRIPT_DIR}/../public/photos"
REKOG_DIR="${SCRIPT_DIR}/../rekog"

umask 0022

cd "${STAGE_DIR}" || exit

for DIR in ${1:-$(find . -mindepth 1 -maxdepth 1 -type d | sort)}
do

    GALLERY=$(echo "${DIR}" | cut -d'/' -f2)
    if ! echo "${GALLERY}" | grep -Eq '^[a-z0-9]+$'
    then
        echo "Gallery name must consist only of digits and lowercase letters: ${GALLERY}"
        exit 1
    fi
    cd "${STAGE_DIR}/${GALLERY}" || exit
    echo "${GALLERY}"

    echo "  renaming photos"
    rename 's/\.jpe?g$/.jpg/i' ./*.JPG ./*.JPEG ./*.jpeg 2>/dev/null

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

        if [ ! -f "${PHOTO_DIR}/${GALLERY}/${PHOTO}" ]
        then
            echo -n "    ${PHOTO} ... "
            if [ ${IMGP} -eq 1 ]
            then
                cp "${FILE}" "${PHOTO_DIR}/${GALLERY}/${PHOTO}"
                imgp --res 600x600 --overwrite --mute "${PHOTO_DIR}/${GALLERY}/${PHOTO}"
            else
                convert -resize 600x600 "${FILE}" "${PHOTO_DIR}/${GALLERY}/${PHOTO}"
            fi
            echo "resized for public"
        fi

        if [ ! -f "${REKOG_DIR}/${GALLERY}/${PHOTO}" ]
        then
            echo -n "    ${PHOTO}"
            cp "${FILE}" "${REKOG_DIR}/${GALLERY}/${PHOTO}"
            while [ $(stat --printf="%s" "${REKOG_DIR}/${GALLERY}/${PHOTO}") -gt "${SIZE}" ]
            do
                echo -n " ."
                if [ ${IMGP} -eq 1 ]
                then
                    imgp --res 50 --overwrite --mute "${REKOG_DIR}/${GALLERY}/${PHOTO}"
                else
                    mogrify -resize 50x50% "${REKOG_DIR}/${GALLERY}/${PHOTO}"
                fi
            done
            echo " resized for Rekognition API"
        fi

    done
    IFS=$SAVEIFS

done
