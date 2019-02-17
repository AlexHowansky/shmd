#!/bin/bash

if ! hash aws 2>/dev/null
then
    echo "ERROR: Requires awscli. See https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html"
    exit 1
fi

if [ ${#} -ne 1 ]
then
    echo "Usage: ${0} <collection>";
    exit
fi

aws --output json rekognition list-faces  --collection-id ${1}
