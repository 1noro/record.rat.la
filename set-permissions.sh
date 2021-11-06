#!/bin/sh
chmod -R 755 .
find . -type f -exec chmod 644 -- {} +
chmod 774 set-permissions.sh
chmod 774 rss-update.py
