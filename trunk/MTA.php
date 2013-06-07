<?php
/**
 * Project: pear Mail Transfer Agent(MTA)<br />
 * File:    MTA.php<br />
 * Dependency: {@link http://svn.oops.org/wsvn/PHP.pear_myException pear_myException}
 *
 * pear_MTA는 php mail function을 대체할 수 있으며, smtp server나
 * smtp daemon이 없더라도 자체적으로 메일을 발송할 수 있는 기능을
 * 제공한다.
 *
 * 또한 alternative/mixed 방식의 mail body를 생성하는 method를 제
 * 공하며, 발송 시 중복된 메일 주소를 압축하여 중복된 메일 서버에
 * 여러번 접근하지 않도록 최적화 되어 있다.
 *
 * @category   Networking
 * @package    MTA
 * @author     JoungKyun.Kim <http://oops.org>
 * @copyright  (c) 1997-2013 OOPS.org
 * @license    BSD License
 * @version    SVN: $Id$
 * @link       http://pear.oops.org/package/MTA
 * @filesource
 */

/**
 * import myException class
 *
 * MTA API는 pear.oops.org/myException pear pacage에
 * 의존성이 있다.
 */
require_once 'myException.php';

/**
 * import MTA_Generate class
 */
require_once 'MTA/MTA_Generate.php';

/**
 * MTA 없이 메일을 발송하기 위한 API
 *
 * @package MTA
 */
Class MTA extends MTA_Generate {
	// {{{ properties
	/**#@+
	 * @access public
	 */
	/**
	 * 입력한 문자셋
	 * @var string
	 */
	public $charset = 'utf-8';
	/**
	 * send method 실행시 debug message 출력
	 * @var boolean
	 */
	public $verbose = false;
	/**#@-*/
	/**
	 * 내부적으로 사용할 socket descriptor
	 * @access protected
	 * @var resource
	 */
	protected $sock = null;
	// }}}

	// {{{ (void) MTA::__construct
	/**
	 * MTA class 초기화
	 */
	function __construct () {}
	// }}}

	// {{{ (object) public  MTA::send ($o)
	/**
	 * 메일 발송
	 *
	 * @access public
	 * @return object 발송 결과(status, error properities)를 반환한다.
	 * @param  object $o mail object
	 *       - o->rpath  : return path (optional)
	 *       - o->from   : Sender address
	 *       - o->to     : Reciever address
	 *       - o->cc     : See also reciever address
	 *       - o->bcc    : Hidden see also reciever address
	 *       - o->subjet : mail subject
	 *       - o->body   : mail contents
	 *       - o->pbody  : planin/text mail contents (optional)
	 *       - o->attach : attached files (array / optional)
	 */
	public function send ($o) {
		$template = $this->source ($o);
		$r = $this->socket_send ($o, $template);

		return $r;
	}
	// }}}

	// {{{ (string) public MTA::source ($v)
	/**
	 * 주어진 정보를 이용하여 raw mail body를 alternative/mixed
	 * 형식으로 반환
	 *
	 * @access public
	 * @return string If occur error, throw excption
	 * @param  object $v mail object
	 *       - $v->from   : Sender address
	 *       - $v->to     : array of Reciever address
	 *       - $v->cc     : array of See also reciever address
	 *       - $v->bcc    : array of Hidden see also reciever address
	 *       - $v->subjet : mail subject
	 *       - $v->body   : mail contents
	 *       - $v->pbody  : planin/text mail contents (optional)
	 *       - $v->attach : attached files (array / optional)
	 */
	public function source ($v) {
		$template = file_get_contents ('MTA/template.txt', true);

		$o = new stdClass;
		foreach ( $v as $key => $val ) {
			if ( $key == 'attach' ) {
				$o->attach = $val;
				continue;
			}

			if ( ! preg_match ('/^utf[-]?8$/i', $this->charset) )
				$o->$key = iconv ($this->charset, 'utf-8', $val);
			else
				$o->$key = $val;
		}

		$this->addr ($o->from);
		if ( is_array ($o->to) ) {
			foreach ( $o->to as $val ) {
				$this->addr ($val);
				$to .= $val . ', ';
			}
			$o->to = preg_replace ('/\,[\s]*$/', '', $to);
			unset ($to);
		} else
			$this->addr ($o->to);

		if ( $o->cc ) {
			if ( is_array ($o->cc) ) {
				foreach ( $o->cc as $val ) {
					$this->addr ($val);
					$cc .= $val . ', ';
				}
				$o->cc = preg_replace ('/\,[\s]*$/', '', $cc);
				unset ($cc);
			} else
				$this->addr ($o->cc);

			$o->cc = 'CC: ' . $o->cc . "\r\n";
		}

		if ( $o->bcc ) {
			if ( is_array ($o->bcc) ) {
				foreach ( $o->bcc as $val ) {
					$this->addr ($val) . ', ';
					$bcc .= $val . ', ';
				}
				$o->bcc = preg_replace ('/\,[\s]*$/', '', $bcc);
				unset ($bcc);
			} else
				$this->addr ($o->bcc);

			$o->bcc = 'BCC: ' . $o->bcc . "\r\n";
		}

		if ( ! $o->pbody )
			$o->pbody = strip_tags ($o->body);

		$o->date = $this->date ();
		$o->msgid = $this->msgid ();
		$o->subject = $this->encode ($o->subject);
		$o->boundary = $this->boundary ();
		$o->subboundary = $this->boundary ();

		$o->body = $this->encode ($o->body, true);
		$o->pbody = $this->encode ($o->pbody, true);

		$attaches = $this->attach ($o->attach, $o->boundary);

		$src = array (
			'/@MESSAGE_ID@/', '/@DATE@/', '/@FROM@/', '/@TO@/', '/@CC@/',
			'/@BCC@/', '/@SUBJECT@/', '/@BOUNDARY@/', '/@SUB_BOUNDARY@/',
			'/@PLAINBODY@/', '/@BODY@/', '/@ATTCHED@/'
		);
		$dst = array (
			$o->msgid, $o->date, $o->from, $o->to,
			$o->cc, $o->bcc, $o->subject, $o->boundary,
			$o->subboundary, $o->pbody, $o->body, $attaches
		);

		return preg_replace ($src, $dst, $template);
	}
	// }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim: set filetype=php noet sw=4 ts=4 fdm=marker:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
?>
