FOR /f ”delims=[] tokens=4" %%i 
	IN (‘nc -l -p 3333 -n -v 2^>^&1 ^| find ^"from^”’) 
	DO set IP=%%i

netsh advfirewall firewall add rule name="Delete" dir=in remoteip=%IP% localport=any protocol=TCP action=block > NUL
