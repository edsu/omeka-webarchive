#!/bin/sh
# Download a 25 MB sample WACZ from Webrecorder's example-webarchive repository.
set -e
cd "$(dirname "$0")"
curl -fL -o archive.wacz.part \
    https://raw.githubusercontent.com/webrecorder/example-webarchive/main/items/birth-web/archive.wacz
mv archive.wacz.part archive.wacz
echo "Saved $(pwd)/archive.wacz"
