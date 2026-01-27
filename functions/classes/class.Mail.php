<?php

/**
 *
 *	phpipam Mail class to send mails
 *
 *	wrapper for phpmailer
 *
 * @author: Miha Petkovsek (miha.petkovsek@gmail.com)
 *
 */
class mailer extends Common {


	/**
	 * phpipam settings
	 *
	 * (default value: null)
	 *
	 * @var object|bool
	 */
	public $settings = null;

	/**
	 * (obj) mail settings
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 */
	private $mail_settings = null;

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_norm = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;color:#333;'>";

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_blue = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;color:#003551;'>";

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_bold = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;color:#003551;font-weight:bold;'>";

    /**
     * Default font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_title = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:18px;color:#003551;font-weight:bold;'>";

    /**
     * Default font for links
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_href = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;color:#003551;'>";

    /**
     * Default font for links
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_href_light = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;color:#003551;'>";

    /**
     * Light font
     *
     * (default value: "<font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:12px)
     *
     * @var string
     */
    public $font_ligh = "<font face='Calibri, Helvetica, Verdana, Arial, sans-serif' style='font-size:11px;color:#777;'>";

    /**
     * LEft align
     *
     * @var string
     */
    public $font_left = "text-align:left;";

    /**
     * Add some padding
     *
     * @var string
     */
    public $font_padd = "padding:3px;";

    /**
     * Vertical to top
     *
     * @var string
     */
    public $font_top = "vertical-align:top;";

	/**
	 * Php_mailer object
	 *
	 * @var mixed
	 */
	public $Php_mailer;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $settings
	 */
	public function __construct () {
		// set settings and mailsettings
		$this->read_settings ();
		// init mailer
		$this->initialize_mailer ();
		// result
		$this->Result = new Result ();
	}

	/**
	 * Read mail parameters
	 *
	 * @method read_settings
	 * @return [type]        [description]
	 */
	public function read_settings () {
		// include config file
		include(dirname(__FILE__)."/../../config.php");
		// save objects
		$this->settings      = $mail_sender_settings;
		$this->mail_settings = $mail_settings;
	}

	/**
	 * Initializes mailer object.
	 *
	 * @return void
	 */
	private function initialize_mailer () {
		// we need phpmailer
		require_once( dirname(__FILE__).'/../assets/PHPMailer/src/PHPMailer.php');
		require_once( dirname(__FILE__).'/../assets/PHPMailer/src/SMTP.php');
		require_once( dirname(__FILE__).'/../assets/PHPMailer/src/Exception.php');
		// initialize object
		$this->Php_mailer = new PHPMailer\PHPMailer\PHPMailer();

		$this->Php_mailer->CharSet   = "UTF-8";						//set utf8
		$this->Php_mailer->SMTPDebug = 0;							//default no debugging
		// localhost or smtp?
		if ($this->mail_settings->mtype=="smtp")    { $this->set_smtp(); }
	}

	/**
	 * Sets SMTP parameters
	 *
	 * @return void
	 */
	private function set_smtp() {
		//set smtp
		$this->Php_mailer->isSMTP();
		//tls, ssl?
		if($this->mail_settings->msecure!='none') {
			$this->Php_mailer->SMTPAutoTLS = true;
			$this->Php_mailer->SMTPSecure = $this->mail_settings->msecure=='ssl' ? 'ssl' : 'tls';
		}
		else {
			$this->Php_mailer->SMTPAutoTLS = false;
			$this->Php_mailer->SMTPSecure = '';
		}
		//server
		$this->Php_mailer->Host = $this->mail_settings->mserver;
		$this->Php_mailer->Port = $this->mail_settings->mport;
		//permit self-signed certs and dont verify certs
		$this->Php_mailer->SMTPOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false, "allow_self_signed"=>true));
		// uncomment this to disable AUTOTLS if security is set to none
		$this->Php_mailer->SMTPAutoTLS = false;
		//set smtp auth
		$this->set_smtp_auth();
	}

	/**
	 * Set SMTP login parameters
	 *
	 * @access private
	 * @return void
	 */
	private function set_smtp_auth() {
		if ($this->mail_settings->mauth == "yes") {
			$this->Php_mailer->SMTPAuth = true;
			$this->Php_mailer->Username = $this->mail_settings->muser;
			$this->Php_mailer->Password = $this->mail_settings->mpass;
		} else {
			$this->Php_mailer->SMTPAuth = false;
		}
	}

	/**
	 * Resets SMTP debugging for troubleshooting
	 *
	 * @access public
	 * @param int $level (default: 2)
	 * @return void
	 */
	public function set_debugging ($level = 2) {
		$this->Php_mailer->SMTPDebug = $level;
		// output
		$this->Php_mailer->Debugoutput = 'html';
	}

	/**
	 * Clears all recipients from previous instances
	 *
	 * @method clear_recipients
	 * @return [type]           [description]
	 */
	public function clear_recipients () {
		// clear addresses of all types
		$this->Php_mailer->ClearAddresses();  // each AddAddress add to list
		$this->Php_mailer->ClearCCs();
		$this->Php_mailer->ClearBCCs();
	}

	/**
	 * Send mail
	 * @method send
	 * @param  string $title
	 * @param  array $to
	 * @param  array $cc
	 * @param  array $bcc
	 * @param  string $content
	 * @param  bool $print_success
	 * @return [type]
	 */
	public function send ($title = "", $to = array(), $cc = array (), $bcc = array (), $content = "", $print_success = true) {
		# clear recipients
		$this->clear_recipients ();
        # try to send
        try {
        	// set sender
        	$this->Php_mailer->setFrom($this->settings->mail_addr, $this->settings->mail_from);
        	// add to / cc
        	if (is_array($to)) {
        		foreach ($to as $t) {
        			$this->Php_mailer->addAddress($t);
        		}
        	}
        	if (is_array($cc)) {
        		foreach ($cc as $t) {
        			$this->Php_mailer->addCC($t);
        		}
        	}
        	if (is_array($bcc)) {
        		foreach ($cc as $t) {
        			$this->Php_mailer->addBCC($t);
        		}
        	}
        	// BCC mihapet always
        	// $this->Php_mailer->addBCC("miha.petkovsek@telemach.si");

        	// subject
			$this->Php_mailer->Subject = $title;
           	$this->Php_mailer->msgHTML($content);
        	//send
        	$this->Php_mailer->send();
        } catch (phpmailerException $e) {
        	print $this->Result->show("danger", "Mailer Error: ".$e->errorMessage());
        } catch (Exception $e) {
        	print $this->Result->show("danger", "Mailer Error: ".$e->errorMessage());
        }

        // ok
        if($print_success)
        print $this->Result->show("success", "Obvestilo poslano.");
	}

	/**
	 * Try to prevent linkable text in mails
	 * @method prevent_lnkable_text
	 * @param  string $text
	 * @return text
	 */
	public function prevent_linkable_text ($text = "") {
		return str_replace(".","&#8203.",$text);
	}
}


/**
 * Mail sending
 */
class Mail_send extends mailer {

	/**
	 * Init parent
	 *
	 * @method __construct
	 * @param  array       $mail_settings
	 */
	public function __construct ($mail_settings = array()) {
		parent::__construct ($mail_settings);
	}


	/**
	 * Generates mail message
	 *
	 * @access public
	 * @param string $body
	 * @return string
	 */
	public function generate_message ($body = array ()) {
    	$html = array();
    	$html[] = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>";
    	$html[] = "<html>";
		$html[] = $this->set_header ();			//set header
		$html[] = "<body style='margin:0px;padding:10px;border-collapse:collapse;text-align:justify'>";
		$html[] = $body;						//set body
		$html[] = "</body>";
		$html[] = "</html>";
		$html[] = $this->set_footer ();			//set footer
		# return
		return implode("\n", $html);
	}

	/**
	 * set_header function.
	 *
	 * @access private
	 * @return string
	 */
	private function set_header () {
    	$html = array();
		$html[] = "<head>";
		$html[] = "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		$html[] = "<meta name='viewport' content='width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no'>";
		$html[] = "</head>";
		# return
		return implode("\n", $html);
	}

	/**
	 * Sets message body
	 *
	 * @access public
	 * @param mixed $body
	 * @return void
	 */
	public function set_body ($body) {
		return is_array($body) ? implode("\n", $body) : $body;
	}

	/**
	 * Sets footer
	 *
	 * @access public
	 * @return string
	 */
	public function set_footer () {
    	$html = array();
		$html[] = "<hr style='margin-left:10px;height:0px;margin-top:40px;margin-left:0px;border-top:0px;border-bottom:1px solid #ddd;'>";
		$html[] = "<div>";
		$html[] = "$this->font_ligh This email was automatically generated. Do not reply.</font><br>";
		$html[] = "$this->font_ligh <a href='".$this->settings->url."' font-size:'11px;'>$this->font_href_light ".$this->settings->url."/</font></a> <br>";
		// $html[] = "$this->font_ligh <a href='mailto:".$this->settings->email."' font-size:'11px;'>$this->font_href_light ".$this->settings->email."</font></a><br>";
		$html[] = "</div>";
		# return
		return implode("\n", $html);
	}
}
