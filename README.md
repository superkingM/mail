# mail
一个用于读取邮箱邮件的拓展包
# required
- imap拓展
# Useage
> composer require superkingm/mail
# configuration
- pop3配置支持网易邮箱
```
        $username = 'xxxx@163.com';
        $password = 'xxxxx';//邮箱登录密码
        $email_address = 'xxxx@163.com';
        $mail_server = 'pop.163.com';
        $server_type = 'pop3';
        $port = 995;
        $ssl = true;
```

- imap配置方式
```
        $username = 'xxxx@qq.com';
         $password = 'dsaffsdfsdfsdfd';//授权码
         $email_address = 'xxxx@qq.com';
         $mail_server = 'imap.qq.com';
         $server_type = 'imap';
         $port = 143;
         $ssl = false;
```

# detail
```
        //pop3或imap配置信息

        $mail = new MailBox($username, $password, $email_address, $mail_server, $server_type, $port, $ssl);
        $mail->connect();
        $mail->getMailTotal();//获取邮件总数,mid 从1到总数
        $head = $mail->getImapHeader($mid);//获取原始的邮件头部信息
        $mail->getHeaders($mid);//获取头部信息例如 发件人，主题，收件人等
        $mail->getBody($mid);//获取邮件内容
        $mail->markMailRead($mid);//将邮件标记为已读
        $mail->markMailUnRead($mid);//将邮件标记为未读
        $mail->isUnread($head);//判断邮件是否被读取
        $mail->deleteMail($mid);//删除邮件
        $mail->getDate($mid);//获取邮件时间
```
