<?php

namespace MailBox;


class MailBox
{
    private $server = '';
    private $username = '';
    private $password = '';
    private $marubox = '';
    private $email = '';
    private $ssl = false;
    private $server_type = 'imap';

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
            $str_connect = '{' . $mail_server . ':' . $port . '/pop3' . ($ssl ? "/ssl/novalidate-cert" : "") . '}INBOX';
        }
        $this->server = $str_connect;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email_address;
        $this->ssl = $ssl;
        $this->server_type = $server_type;
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
    public function getMailTotal()
    {
        if (!$this->marubox) return false;
        $tmp = imap_num_msg($this->marubox);
        return is_numeric($tmp) ? $tmp : false;
    }

    /**
     * 获取邮件的头部原始信息
     */
    public function getImapHeader($mid)
    {
        return imap_headerinfo($this->marubox, $mid);
    }

    /**
     * 标记邮件成已读
     * @param $mid
     * @return bool
     */
    public function markMailRead($mid)
    {
        return imap_setflag_full($this->marubox, $mid, '\\Seen');
    }


    /**
     * 标记邮件成未读
     * @param $mid
     * @return bool
     */
    public function markMailUnRead($mid)
    {
        return imap_clearflag_full($this->marubox, $mid, '\\Seen');
    }

    /**
     * 判断是否阅读了邮件 $headerinfo getImapHeader 的返回值
     * @param $headerinfo
     * @return bool
     */
    public function isUnread($headerinfo)
    {
        if (($headerinfo->Unseen == 'U') || ($headerinfo->Recent == 'N')) return true;
        return false;
    }

    /**
     * 删除邮件
     */
    public function deleteMail($mid)
    {
        if (!$this->marubox) return false;
        return imap_delete($this->marubox, $mid, 0);
    }

    /**
     * 获取邮件时间
     */

    public function getDate($mid)
    {
        return strtotime($this->getImapHeader($mid)->MailDate);
    }


    /**获取邮件内容
     * @param $mid 邮件mid
     * @return array|bool|false|string|string[]|null
     */
    public function getBody($mid)
    {
        $body = imap_fetchbody($this->marubox, $mid, 1);
        $encoding = imap_fetchstructure($this->marubox, $mid);
        $code = 3;
        $utf = 'utf-8';
        $split = null;
        if ($encoding->subtype == "ALTERNATIVE") {
            $body = imap_fetchbody($this->marubox, $mid, 2);
            if (isset($encoding->parts)) {
                foreach ($encoding->parts as $part) {
                    if ($part->subtype == 'HTML') {
                        $code = $part->encoding;
                        if (strtolower($part->parameters[0]->attribute) == 'charset') {
                            $utf = $part->parameters[0]->value;
                        }
                    }
                }
            }

            if ($code == 4) {
                $body = imap_qprint($body);
            }

            if ($code == 3) {
                $body = base64_decode($body);
            }

            if (strtolower($utf) != 'utf-8') {
                $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
            }
        }

        if ($encoding->subtype == "MIXED" || $encoding->subtype == "RELATED") {
            if (isset($encoding->parts)) {
                foreach ($encoding->parts as $part) {
                    if ($part->subtype == 'ALTERNATIVE' || $part->subtype == 'RELATED') {
                        if (isset($part->parameters[0]->attribute)) {
                            $split = $part->parameters[0]->value;
                        }
                        foreach ($part->parts as $par) {
                            if ($par->subtype == 'HTML') {
                                $code = $par->encoding;
                                if (strtolower($par->parameters[0]->attribute) == 'charset') {
                                    $utf = $par->parameters[0]->value;
                                }
                            }

                            if ($par->subtype == 'ALTERNATIVE'||$par->subtype =='RELATED') {

                                if (isset($par->parameters[0]->attribute)) {
                                    $split = $par->parameters[0]->value;
                                }
                                foreach ($par->parts as $pa) {
                                    if ($pa->subtype == 'HTML') {
                                        $code = $pa->encoding;
                                        if (strtolower($pa->parameters[0]->attribute) == 'charset') {
                                            $utf = $pa->parameters[0]->value;
                                        }
                                    }
                                }

                            }
                        }


                    }

                    if ($part->subtype == 'HTML') {
                        $code = $part->encoding;
                        if (strtolower($part->parameters[0]->attribute) == 'charset') {
                            $utf = $part->parameters[0]->value;
                        }
                    }
                }
            }
            if ($split) {

                $body = explode($split, $body);
                foreach ($body as $k => $bo) {
                    if (strpos($bo, 'text/html')) {
                        $body = $body[$k];
                    }
                }


            }

            if ($code == 4) {
                if (strpos($body, 'quoted-printable')) {
                    $body = explode('quoted-printable', $body)[1];
                }
                $body = imap_qprint($body);
                if (strtolower($utf) != 'utf-8') {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
            }

            if ($code == 3) {
                if (strpos($body, 'base64')) {
                    $body = explode('base64', $body)[1];
                }
                $body = base64_decode($body);
                if (strtolower($utf) != 'utf-8') {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
            }
        }

        if ($encoding->subtype == "HTML") {
            $code = $encoding->encoding;
            if (strtolower($encoding->parameters[0]->attribute) == 'charset') {
                $utf = $encoding->parameters[0]->value;
            }
            if ($code == 3) {
                if (strpos($body, 'base64')) {
                    $body = explode('base64', $body)[1];
                }
                $body = base64_decode($body);
                if (strtolower($utf) != 'utf-8') {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
            }

            if ($code == 5) {

            }
        }


        return $body;

    }

    /**
     * 获取邮件基本信息
     * @param $mid
     * @return array
     */
    public function getHeaders($mid)
    {
        $raw_header_info = imap_headerinfo($this->marubox, $mid);
        $from = $raw_header_info->from[0]->mailbox . '@' . $raw_header_info->from[0]->host;
        $subject = $this->decodeStr($raw_header_info->subject);
        $to = $this->decodeStr($raw_header_info->toaddress);
        $fromName = isset($raw_header_info->from[0]->personal) ? $this->decodeStr($raw_header_info->from[0]->personal) : $this->decodeStr($raw_header_info->fromaddress);
        $toOth = isset($raw_header_info->ccaddress) ? $this->decodeStr($raw_header_info->ccaddress) : '';
        $date = $raw_header_info->udate;
        return [
            'from' => $from,
            'fromName' => $fromName,
            'to' => $to,
            'toOth' => $toOth,
            'subject' => $subject,
            'date' => $date
        ];
    }

    /**
     * 关闭 IMAP 流
     */
    public function closeMailbox()
    {
        if (!$this->marubox) return false;
        imap_close($this->marubox, CL_EXPUNGE);
    }

    /**
     * 对象销毁前关闭邮箱
     */
    public function __destruct()
    {
        $this->closeMailbox();
    }


    private function decodeStr($str)
    {
        $code = imap_mime_header_decode($str);
        $code_type = ['gb2312', 'gb18030', 'gbk', 'GBK', 'GB2312', 'GB18030'];
        $str = '';

        foreach ($code as $c) {
            if (in_array($c->charset, $code_type)) {
                $str .= mb_convert_encoding($c->text, 'UTF-8', 'GBK');
            } else {
                $str .= $c->text;
            }
        }

        return $str;
    }

}