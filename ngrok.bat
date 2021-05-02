@echo off
cd "C:\ngrok"

ngrok http -host-header=money.local 80

pause