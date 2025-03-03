#!/bin/bash -e

SCRIPT_DIR=$(cd -P "$(dirname "${BASH_SOURCE[0]}")" && pwd)
BASE_DIR="${SCRIPT_DIR}/.."

READABLE="pages public src staging vendor"
WRITABLE="hotFolder orders var"

cd "${BASE_DIR}"

chmod 644 config.json

echo "making readable:"
for DIR in ${READABLE}
do
    echo "  ${DIR}"
    find "${DIR}" -type d -exec chmod 755 {} \;
    find "${DIR}" -type f -exec chmod 644 {} \;
done

echo "making writable:"
for DIR in ${WRITABLE}
do
    echo "  ${DIR}"
    find "${DIR}" -type d -exec chmod 777 {} \;
done
