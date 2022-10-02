### Push to one.com RSYNC over SCP
* Rsync is usually faster than sftp
* rsync --checksum allows to only transfer files that changed content and ignoring timestamp (as timestamp will always be new because hugo rebuild complete site)

Reference:
* https://zellwk.com/blog/github-actions-deploy/
* https://discourse.gohugo.io/t/solved-are-static-files-updated-when-not-changed/15023/11


1/ On local 
```
ssh-keygen -t rsa -b 4096 -C "ga@githubaction.com"
(leave passprass empty)
filename=github-actions
```

2/ transfer .pub to one.com and install it as authorized keys
```
cat github-actions.pub >> ~/.ssh/authorized_keys
```

3/ install private-key in github-actions private key and use it in GA workflow
