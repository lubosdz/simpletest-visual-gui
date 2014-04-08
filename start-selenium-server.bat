echo Starting Selenium HQ standalone server
rem echo off

java -jar d:\_projects\selenium\selenium-server-standalone-2.41.0.jar -log ./log/selenium.log -trustAllSSLCertificates

rem java -jar d:\_projects\selenium\selenium-server-standalone-2.41.0.jar -help
rem pause