#!/bin/bash

npm ci
npm run build

rm ./brightcove-video-connect.zip

git archive --output=brightcove-video-connect.zip HEAD
zip -ur brightcove-video-connect.zip dist
