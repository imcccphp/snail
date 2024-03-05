<?php
namespace Imccc\Snail\Services;

class MailService
{
    public function sendMail($to, $subject, $body, $fromEmail = null, $fromName = null, $replyTo = null, $cc = null, $bcc = null, $attachments = array())
    {
        // 构建邮件头
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";

        // 发件人
        if ($fromEmail && $fromName) {
            $headers .= "From: $fromName <$fromEmail>\r\n";
        } elseif ($fromEmail) {
            $headers .= "From: $fromEmail\r\n";
        }

        // 回复地址
        if ($replyTo) {
            $headers .= "Reply-To: $replyTo\r\n";
        }

        // 抄送
        if ($cc) {
            $headers .= "Cc: $cc\r\n";
        }

        // 密件抄送
        if ($bcc) {
            $headers .= "Bcc: $bcc\r\n";
        }

        // 添加附件
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $file = $attachment['file'];
                $filename = $attachment['filename'];
                $filetype = $attachment['filetype'];
                $content = $attachment['content'];

                // 以 Base64 编码内容
                $content = chunk_split(base64_encode($content));

                // 添加附件头
                $headers .= "Content-Type: $filetype; name=\"$filename\"\r\n";
                $headers .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
                $headers .= "Content-Transfer-Encoding: base64\r\n";
                $headers .= "X-Attachment-Id: " . md5($filename) . "\r\n";

                // 添加附件内容
                $body .= "--boundary\r\n";
                $body .= "Content-Type: $filetype; name=\"$filename\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= "$content\r\n";
            }
            // 添加结束 boundary
            $body .= "--boundary--\r\n";
        }

        // 发送邮件
        return mail($to, $subject, $body, $headers);
    }
}
