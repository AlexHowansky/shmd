#!/bin/bash

if ! hash aws 2>/dev/null
then
    echo "ERROR: Requires awscli. See https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html"
    exit 1
fi

for COLLECTION in $(aws --output text rekognition list-collections | grep COLLECTIONIDS | awk '{ print $2 }')
do
    echo "Collection: ${COLLECTION}"
    aws --output json rekognition describe-collection --collection-id ${COLLECTION}
    echo
done
