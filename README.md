# mail
一个用于读取邮箱邮件的拓展包
# Useage
> composer require superkingm/mail

```
        $username = 'xxxxxx@qq.com';//名称
        $password = 'xxxxxxx';//邮箱授权码
        $email_address = 'xxxxxx@qq.com';//邮箱地址
        $mail_server = 'imap.qq.com';//服务器地址
        $server_type = 'imap';//协议
        $port = 143;//端口

        $mail = new MailBox($username, $password, $email_address, $mail_server, $server_type, $port);
        $mail->connect();
        $mail->get_mail_total();//获取邮件总数,mid 从1到总数
        $head = $mail->get_imap_header($mid);//获取原始的邮件头部信息
        $mail->get_header_info($head);//获取头部信息例如 发件人，主题，收件人等
        $mail->get_body($mid);//获取邮件内容
```
