#!/usr/bin/env bash

# Change directory to onpay
cd ./onpay

# Get composer
# Locked to version 1.X.X to preserve PHP 5.6 compatibility
EXPECTED_SIGNATURE="e70b1024c194e07db02275dd26ed511ce620ede45c1e237b3ef51d5f8171348d"
php -r "copy('https://getcomposer.org/download/1.10.20/composer.phar', 'composer.phar');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha256', 'composer.phar');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid composer version'
    rm composer.phar
    exit 1
fi

# Remove vendor directory
rm -rf ./vendor

# Run composer install
php composer.phar install

# Remove composer
rm composer.phar

# Change directory back to root
cd ./..

# Remove existing build zip file
rm ./whmcs_onpay.zip

# Rsync contents of folder to new directory that we will use for the build
rsync -Rr ./* ./whmcs_onpay

# Remove directories and files from newly created directory, that we won't need in final build
rm ./whmcs_onpay/build.sh
rm ./whmcs_onpay/onpay/composer.json
rm ./whmcs_onpay/onpay/composer.lock

# Zip contents of newly created directory
cd ./whmcs_onpay
zip -r ../whmcs_onpay.zip ./
cd ..

# Clean up
rm -rf ./whmcs_onpay