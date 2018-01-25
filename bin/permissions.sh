#!/bin/bash

if [ "$UID" != 0 ] ; then
echo "You must be under sudo to change permissions";
exit;
fi



SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";

# including configuration
. $SCRIPT_DIR/permissions.cfg;

# fixing root dir to absolute path
ROOT_DIR=$SCRIPT_DIR/$ROOT_DIR


# directories
for I in "${WRITE_DIRS[@]}"
do
echo ""
echo $I;
    find $ROOT_DIR/$I -type 'd' -print0|xargs -0 chmod $WRITE_MODE_DIR;
    find $ROOT_DIR/$I -type 'd' -print0|xargs -0 chown $USER:$GROUP $ROOT_DIR/$I;
done;

# files
for I in "${WRITE_FILES[@]}"
do
echo ""
echo $I;
    chmod $WRITE_MODE_FILE $ROOT_DIR/$I;
    chown $USER:$GROUP $ROOT_DIR/$I;
done;

# + all_write
# directories
for I in "${WRITE_ALL_DIRS[@]}"
do
echo ""
echo $I;
    find $ROOT_DIR/$I -type 'd' -print0|xargs -0 chmod $WRITE_ALL_MODE_DIR;
    find $ROOT_DIR/$I -type 'd' -print0|xargs -0 chown $USER:$GROUP $ROOT_DIR/$I;
done;
