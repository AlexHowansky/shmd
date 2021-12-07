To mount a Windows share on Linux:

mount \
    -t cifs \
    -o username=<linux_name>,uid=<linux_uid>,gid=<linux_gid> \
    //<win_ip>/<win_share> \
    /path/to/local/app/print/ \
