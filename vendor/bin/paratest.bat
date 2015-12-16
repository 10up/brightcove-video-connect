@ECHO OFF
SET BIN_TARGET=%~dp0/../brianium/paratest/bin/paratest
php "%BIN_TARGET%" %*
