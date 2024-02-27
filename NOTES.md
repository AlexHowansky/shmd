To mount a Windows share on Linux:

```
mount \
    -t cifs \
    -o username=<linux_name>,uid=<linux_uid>,gid=<linux_gid> \
    //<win_ip>/<win_share> \
    /path/to/local/app/print/ \
```

To proxy from a Windows host to a service running in WSL2:

```
wsl hostname -I
netsh interface portproxy add v4tov4 listenport=80 listenaddress=0.0.0.0 connectport=80 connectaddress=<ip of wsl>
```

To list existing proxies:

```
netsh interface portproxy show all
```

To delete existing proxies:

```
netsh interface portproxy delete v4tov4 22 0.0.0.0
netsh interface portproxy delete v4tov4 80 0.0.0.0
```

To allow inbound HTTP connections on the Windows host:

```
netsh advfirewall firewall add rule name="SHMD Photo App" dir=in action=allow protocol=TCP localport=80
```

To delete:

```
netsh advfirewall firewall delete rule name="SHMD Photo App" protocol=TCP localport=80
```
