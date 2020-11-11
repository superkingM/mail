<?php

namespace Mail;


class Mail
{
    private $server = '';
    private $username = '';
    private $password = '';
    private $marubox = '';
    private $email = '';

    public function __construct($username, $password, $email_address, $mail_server, $server_type, $port, $ssl = false)
    {
        if ($server_type == 'imap') {
            if ($port == '') $port = '143';
            $str_connect = '{' . $mail_server . '/imap:' . $port . '}INBOX';
        } else {
            if ($port == '') $port = '110';
            $str_connect = '{' . $mail_server . ':' . $port . '/pop3' . ($ssl ? "/ssl" : "") . '}INBOX';
        }
        $this->server = $str_connect;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email_address;
    }

    public function connect()
    {
        $this->marubox = @imap_open($this->server, $this->username, $this->password, 0);
        if (!$this->marubox) {
            echo "Error: Connecting to mail server<br/>";
            echo $this->server;
            exit;
        }
    }

    /**
     * 获取邮件总数
     */
    public function get_mail_total()
    {
        if (!$this->marubox) return false;
        $tmp = imap_num_msg($this->marubox);
        return is_numeric($tmp) ? $tmp : false;
    }

    /**
     * 获取邮件的头部
     */
    public function get_imap_header($mid)
    {
        return imap_headerinfo($this->marubox, $mid);
    }

    /**
     * 格式化头部信息 $headerinfo get_imap_header 的返回值
     */
    public function get_header_info($mail_header)
    {
        $sender = $mail_header->from[0];
        $sender_replyto = $mail_header->reply_to[0];
        if (strtolower($sender->mailbox) != 'mailer-daemon' && strtolower($sender->mailbox) != 'postmaster') {
            $mail_details = array(
                'from' => strtolower($sender->mailbox) . '@' . $sender->host,
                'fromName' => $this->_decode_GBK($sender->personal),
                'toOth' => strtolower($sender_replyto->mailbox) . '@' . $sender_replyto->host,
                'toNameOth' => $this->_decode_GBK($sender_replyto->personal),
                'subject' => $this->_decode_GBK($mail_header->subject),
                'to' => strtolower($this->_decode_mime_str($mail_header->toaddress))
            );
        }
        return $mail_details;
    }

    public function get_body($mid)
    {
//        return   imap_fetchstructure($this->marubox,$mid);
        $body = imap_fetchbody($this->marubox, $mid, 1);
        $body = base64_decode($body);
        if (mb_detect_encoding($body,'GBK')){
            $body =  mb_convert_encoding($body,'UTF-8','GBK');
        }
        return $body;
    }

    private function _decode_GBK($string)
    {
        $newString = '';
        $string = str_replace('=?GBK?B?', '', $string);
        $newString = base64_decode($string);
        return $newString;

    }

    private function _decode_mime_str($string, $charset = "UTF-8")
    {
        $newString = '';
        $elements = imap_mime_header_decode($string);
        for ($i = 0; $i < count($elements); $i++) {
            if ($elements[$i]->charset == 'default') $elements[$i]->charset = 'iso-8859-1';
            $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);
        }
        return $newString;
    }

}