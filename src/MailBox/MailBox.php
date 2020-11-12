<?php

namespace MailBox;


class MailBox
{
    private $server = '';
    private $username = '';
    private $password = '';
    private $marubox = '';
    private $email = '';

    /**
     * MailBox constructor.
     * @param $username
     * @param $password
     * @param $email_address
     * @param $mail_server
     * @param $server_type
     * @param $port
     * @param bool $ssl
     * 初始化
     */
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

    /**
     * 连接
     */
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

    /**
     * 获取邮件内容
     * @param $mid
     * @return bool|string
     */
    public function get_body($mid)
    {
        $body = imap_fetchbody($this->marubox, $mid, 1);
        $encoding = imap_fetchstructure($this->marubox, $mid);
        if (!isset($encoding->parts)) {
            if ($encoding->encoding == 3) {
                return base64_decode($body);
            }
        } else {
            $code = 3;
            $param = strtolower($encoding->parameters[0]->value);


            foreach ($encoding->parts as $part) {
                if ($part->encoding == 0) {
                    foreach ($part->parts as $pa) {
                        if ($pa->encoding == 4) {
                            $code = 4;
                        }
                    }
                }
                if ($part->encoding == 4) {
                    $code = 4;
                }
            }

            if ($code == 3) {

                if (!strpos($param, 'part') && !strpos($param, 'nextpart')) {
                    $body = imap_fetchbody($this->marubox, $mid, 2);
                    return base64_decode($body);
                }
                if (strpos($param, 'nextpart')||strpos($param,'part')) {
                    $body = imap_fetchbody($this->marubox, $mid, 2);
                    $body = base64_decode($body);
                    if (mb_detect_encoding($body, 'GBK')) {
                        $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                    }
                    return $body;
                }
                $body = base64_decode($body);
                if (mb_detect_encoding($body, 'GBK')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
                return $body;
            }
            if ($code == 4) {
                if (!strpos($param, 'part') && !strpos($param, 'nextpart')) {
                    $body = imap_fetchbody($this->marubox, $mid, 2);
                    return imap_qprint($body);
                }
                return imap_qprint($body);
            }

        }


        return $body;

    }

    /**
     * 标记邮件成已读
     */
    public function mark_mail_read($mid)
    {
        return imap_setflag_full($this->marubox, $mid, '\\Seen');
    }

    /**
     * 标记邮件成未读
     */
    public function mark_mail_un_read($mid)
    {
        return imap_clearflag_full($this->marubox, $mid, '\\Seen');
    }

    /**
     * 判断是否阅读了邮件 $headerinfo get_imap_header 的返回值
     */
    public function is_unread($headerinfo)
    {
        if (($headerinfo->Unseen == 'U') || ($headerinfo->Recent == 'N')) return true;
        return false;
    }

    /**
     * 删除邮件
     */
    public function delete_mail($mid)
    {
        if (!$this->marubox) return false;
        return imap_delete($this->marubox, $mid, 0);
    }

    /**
     * 获取邮件时间
     */

    public function get_date($mid)
    {
        return strtotime($this->get_imap_header($mid)->MailDate);
    }

    /**
     * 关闭 IMAP 流
     */
    public function close_mailbox()
    {
        if (!$this->marubox) return false;
        imap_close($this->marubox, CL_EXPUNGE);
    }

    /**
     * 对象销毁前关闭邮箱
     */
    public function __destruct()
    {
        $this->close_mailbox();
    }

    /**GBK解码
     * @param $string
     * @return bool|string
     */
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