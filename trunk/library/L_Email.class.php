<?php
/**
 * Email Class
 * Created by JetBrains
 * User: alex
 * Date: 13-8-8
 * Time: 上午10:52
 */

class L_Email {
    /**
     *email address to send
     */
    private $sendAddress;

    /**
     *email title
     */
    private $title;

    /**
     *email content
     */
    private $contents;

    /**
     * @param string $sendAddress 接收者邮箱
     * @param string $title       邮件标题
     * @param string $contents    邮件内容
     */
    function __construct($sendAddress, $title, $contents){
    	include BASE_PATH . '/plugin/PHPMailer/class.phpmailer.php';
        include BASE_PATH . '/plugin/PHPMailer/class.smtp.php';
//        if(!isset($Config['mailConfig'])){
//        	include CONFIG_PATH. '/Mail_config.php';
//        }
        
        $this->sendAddress = $sendAddress;
        $this->title = $title;
        $this->contents = $contents;
    }

    /**
     * 发送邮件
     * @return int  $code    发送成功返回1，或失败返回0
     */
    public function send(){
        $mail = new PHPMailer();
        include CONFIG_PATH. '/Mail_config.php';
        if(1 == $Config['mailConfig']['type']){
            $mail->IsSMTP();                                        // 经smtp发送
            $mail->SMTPAuth = true;                                 // 打开SMTP 认证
            $mail->Host     = $Config['mailConfig']['server'];      // SMTP 服务器
            $mail->Port     = $Config['mailConfig']['port'];        // SMTP 端口
            $mail->Username = $Config['mailConfig']['user'];        // 用户名
            $mail->Password = $Config['mailConfig']['password'];    // 密码
            $mail->From     = $Config['mailConfig']['from'];        // 发信人
            $mail->FromName = $Config['mailConfig']['name'];        // 发信人别名
        }else{
            $mail->IsSendmail();                                    // 系统自带的 SENDMAIL 发送
			//$mail->IsMail();
            $mail->From     = $Config['mailConfig']['sender'];      // 发信人
            $mail->Sender   = $Config['mailConfig']['sender'];      // 发信人
            $mail->FromName = $Config['mailConfig']['name'];        // 发信人别名
            $mail->AddAddress($this->sendAddress);				    // 设置发件人的姓名
        }
        
        //pr($mail); die;

        $mail->AddAddress($this->sendAddress);                      // 收信人
        $mail->WordWrap = 50;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);                                        // 以html方式发送
        $mail->Subject = $this->title;                              // 邮件标题
        $mail->Body = $this->contents;                              // 邮件内空
        $mail->AltBody = "请使用HTML方式查看邮件。";
		
		//pr($mail); die;

        if (!@$mail->Send()) {
            $code = 0;
			//echo $mail->ErrorInfo;
        } else {
            $code = 1;
        }
        return $code;
    }
}
?>