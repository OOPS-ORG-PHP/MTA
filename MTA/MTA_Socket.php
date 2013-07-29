<?php
/**
 * Project: MTA_Socket :: pear_MTA socket abstraction layer<br />
 * File:    MTA/MTA_Socket.php
 *
 * eSNMP_Socket class는 소켓으로 메일을 발송하기 위한 추상
 * 레이어를 제공한다.
 *
 * @category   Networking
 * @package    MTA
 * @subpackage MTA_Socket
 * @author     JoungKyun.Kim <http://oops.org>
 * @copyright  (c) 2013, JoungKyun.Kim
 * @license    BSD License
 * @version    SVN: $Id$
 * @link       http://pear.oops.org/package/MTA
 * @filesource
 */

/**
 * Socket for send mail of MTA API
 *
 * 소켓으로 메일을 발송하기 위한 추상 레이어
 *
 * @package MTA
 */
Class MTA_Socket {
	// {{{ properties
	/**
	 * 에러 메시지
	 * @access public
	 * @var string
	 */
	public $error = null;
	// }}}

	// {{{ protected MTA_Socket::target_object ($o)
	/**
	 * 동일한 호스트에 여러번 접속하지 않도록 하기 위하여 메일 호스트
	 * 별로 모음
	 *
	 * @access protected
	 * @return stdClass
	 * @param  object $o mail object
	 *   <pre>
	 *   stdClass Object
	 *   (
	 *       [to]  => (array) to list
	 *       [cc]  => (array) cc list
	 *       [bcc] => (array) bcc list
	 *   )
	 *   </pre>
	 */
	protected function target_object ($o) {
		if ( is_array ($o->to) )
			$tmp = $o->to;
		else
			$tmp = array ($o->to);

		if ( $o->cc ) {
			if ( is_array ($o->cc) )
				$tmp = array_merge ($tmp, $o->cc);
			else
				$tmp[] = $o->cc;
		}

		if ( $o->bcc ) {
			if ( is_array ($o->bcc) )
				$tmp = array_merge ($tmp, $o->bcc);
			else
				$tmp[] = $o->bcc;
		}

		$target = new stdClass;
		foreach ( $tmp as $addr ) {
			$mail = preg_replace ('/^[^<]+<|>[^>]*$/', '', $addr);
			list ($local, $host) = preg_split ('/@/', $mail);

			if ( $this->check_local ($local, true) == false )
				continue;

			if ( $this->check_domain ($host, true) == false )
				continue;

			if ( ! $target->$host ) {
				$target->$host = new stdClass;

				if ( getmxrr ($host, $mx, $weight) ) {
					foreach ( $weight as $key => $val ) {
						$target->$host->mx[] = $val . ' ' . $mx[$key];
					}
					sort ($target->$host->mx, SORT_NUMERIC);


				} else {
					$target->$host->mx = array ('10 ' . $host);
				}
			}

			$target->$host->rcpt[] = '<' . $mail . '>';
		}

		return $target;
	}
	// }}}

	// {{{ (object) protected MTA_Socket::socket_send ($o, &$template)
	/**
	 * 메일 발송
	 *
	 * @access protected
	 * @return stdClass  발송 결과를 object로 반환한다.
	 *   <pre>
	 *   stdClass Object
	 *   (
	 *       [status]  => (boolean) 성공 실패 여부
	 *       [error]   => (string|null) status false시 에러 메시지
	 *       [rcptlog] => (array) rcpt to에 대한 log
	 *   )
	 *   </pre>
	 *
	 *   RCPT list별로 확인을 위해서 status가 true이더라도 rcptlog를
	 *   확인하는 것이 필요
	 *
	 * @param  object $o mail object
     *   <pre>
     *   stdClass Object
     *   (
	 *       [rpath]  => (string) return path (optional)
	 *       [from]   => (string) Sender address
	 *       [to]     => (array) Reciever address
	 *       [cc]     => (array) See also reciever address
	 *       [bcc]    => (array) Hidden see also reciever address
	 *       [subjet] => (string) mail subject
	 *       [body]   => (string) mail contents
	 *       [pbody]  => (string) planin/text mail contents (optional)
	 *       [attach] => (array) attached files (optional)
     *   )
     *   </pre>
	 * @param string &$template 메일 본문
	 */
	protected function socket_send ($o, &$template) {
		$this->error = false;
		$t = $this->target_object ($o);
		$r = new stdClass;

		foreach ( $t as $vendor => $val ) {
			$this->debug (str_repeat ('-', 60));
			$this->debug ('Trying to ' . $vendor);
			$this->debug (str_repeat ('-', 60));

			$r->$vendor = new stdClass;

			if ( $this->open ($val) === false ) {
				$r->$vendor->status = false;
				$r->$vendor->error = $this->error;
				$this->error = null;
				continue;
			}

			if ( $this->ehlo () === false ) {
				$r->$vendor->status = false;
				$r->$vendor->error = $this->error;
				$this->error = null;
				$this->close ();
				continue;
			}

			if ( $this->mailfrom ($o) === false ) {
				$r->$vendor->status = false;
				$r->$vendor->error = $this->error;
				$this->error = null;
				$this->close ();
				continue;
			}

			if ( isset ($log) )
				unset ($log);

			if ( $this->rcptto ($val, $log) === false ) {
				$r->$vendor->status = false;
				$r->$vendor->error = $this->error;
				$r->$vendor->rcptlog = $log;
				$this->error = null;
				$this->close ();
				continue;
			} else {
				# 일부 실패를 위해서 확인은 해야 함!
				$r->$vendor->rcptlog = $log;
			}

			if ( $this->data ($template) === false ) {
				$r->$vendor->status = false;
				$r->$vendor->error = $this->error;
				$this->error = null;
				$this->close ();
				continue;
			}
			$this->close ();

			$r->$vendor->status = true;
		}

		return $r;
	}
	// }}}

	// {{{ (bool) protected MTA_Socket::ehlo ()
	/**
	 * ehlo 명령 전송
	 *
	 * @access protected
	 * @return bool
	 */
	protected function ehlo () {
		$this->error = false;
		$this->write ('EHLO ' . gethostname ());

		if ( $this->read () === false )
			return false;

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Socket::mailfrom ($o)
	/**
	 * mailfrom 명령 전송
	 * 
	 * @access protected
	 * @return bool
	 * @param  object $o mail object
	 *       - o->from   : Sender address
	 *       - o->to     : array of Reciever address
	 *       - o->cc     : array of See also reciever address
	 *       - o->bcc    : array of Hidden see also reciever address
	 *       - o->subjet : mail subject
	 *       - o->body   : mail contents
	 *       - o->pbody  : planin/text mail contents (optional)
	 *       - o->attach : attached files (array / optional)
	 */
	protected function mailfrom ($o) {
		$this->error = false;
		$rv = $o->rpath ? $o->rpath : $o->from;
		$rv = preg_replace ('/^[^<]*<|>[^>]*$/', '', $rv);

		$chk = preg_split ('/@/', $rv);

		if ( $this->check_local ($chk[0], true) === false ) {
			$this->error = 'Invalid local part of Return-Path';
			return false;
		}

		if ( $this->check_domain ($chk[1], true) === false ) {
			$this->error = 'Invalid domain part of Return-Path';
			return false;
		}

		$this->write ('MAIL From:<' . $rv . '>');

		if ( $this->read () === false )
			return false;

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Socket::rcptto ($o, &$log)
	/**
	 * RCPT 명령 전송
	 * 
	 * @access protected
	 * @return bool
	 * @param  object $o MTA_Socket::target_object method의 rcpt list
	 * @param  array &$log 발송 로그
	 */
	protected function rcptto ($o, &$log) {
		$status = array ();
		$this->error = false;
		if ( ! is_array ($o->rcpt) || ! count ($o->rcpt) ) {
			$this->error = 'No RCPT list';
			return false;
		}

		$no = count ($o->rcpt);
		$fno = 0;

		foreach ( $o->rcpt as $addr ) {
			$this->write ('RCPT To:' . $addr);

			if ( $this->read () === false ) {
				$log[] = $this->error;
				$fno++;
			} else
				$log[] = sprintf ('%s Success', $addr);
		}

		# 모든 rcpt list가 실패
		if ( $fno == $no ) {
			$this->error = 'Failure all RCPT list';
			return false;
		}

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Socket::data (&$v)
	/**
	 * DATA 명령 전송 및 mail body 전송
	 *
	 * @access protected
	 * @return bool
	 * @param  string $v 메일 본문 원본
	 */
	protected function data (&$v) {
		$this->error = false;

		$this->write ('DATA');
		if ( $this->read () === false )
			return false;

		// self recieve
		$smtp = gethostname ();
		$remote = $_SERVER['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

		$received =
			"Received: from localhost ([{$remote}])\r\n" .
			"    by {$smtp} with ESMTP id " . uniqid () . ";\r\n" .
			'    ' . $this->date () . "\r\n";

		$this->write ($received . $v . "\r\n.");

		if ( $this->read () === false )
			return false;

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Socket::open ($o)
	/**
	 * @access protected
	 * @return bool
	 * @param stdClass $o MTA_Socket::target_object method 반환값
	 */
	protected function open ($o) {
		$this->error = false;
		$mxno = count ($o->mx);

		for ( $i=0; $i<$mxno; $i++ ) {
			$mx = preg_replace ('/^[0-9]+[\s]*/', '', $o->mx[$i]);


			$this->sock = @stream_socket_client (
				'tcp://' . $mx .':25', $errno, $errstr, 3, STREAM_CLIENT_CONNECT
			);

			if ( is_resource ($this->sock) ) {
				$this->debug ('  - Connecting to ' . $mx . '... Success');
				if ( ($buf = $this->read ()) === false ) {
					fclose ($this->sokc);
					continue;
				}
				return true;
			} else
				$this->debug ('  - Connecting to ' . $mx . '... Failure');
		}

		$this->error = $errstr;

		return false;
	}
	// }}}

	// {{{ (string) protected MTA_Socket::read ()
	/**
	 * 소켓을 읽는다.
	 *
	 * @access protected
	 * @return string
	 */
	protected function read () {
		$this->error = null;
		$buf = fread ($this->sock, 8102);

		$code = substr ($buf, 0, 3);
		if ( ! preg_match ('/^(220|221|250|251|354)$/', $code) ) {
			$this->error = $buf;
			return false;
		}

		$rbuf = preg_replace ('/^/m',    '           ', $buf);
		$rbuf = preg_replace ('/^           /', '  * ', $rbuf);
		$this->debug ($rbuf);
		return $buf;
	}
	// }}}

	// {{{ (int) protected MTA_Socket::write ($m)
	/**
	 * 데이터의 끝에 "\r\n"을 붙여서 전송한다.
	 *
	 * @access protected
	 * @return int 전송한 데이터 길이
	 * @param string $m 전송할 데이터
	 */
	protected function write ($m) {
		$this->debug ('  * ' . $m);
		$m .= "\r\n";
		return fwrite ($this->sock, $m, strlen ($m));
	}
	// }}}

	// {{{ (void) protected MTA_Socket::close ()
	/**
	 * Quit 명령을 실행한 후, socket을 닫는다.
	 *
	 * @access protected
	 * @return void
	 */
	protected function close () {
		$this->write ('Quit');
		if ( is_resource ($this->sock) )
			fclose ($this->sock);
	}
	// }}}

	// {{{ (void) protected MTA_Socket::debug ($m)
	/**
	 * verbose properity가 true로 설정이 되면 debug 메시지를
	 * 출력한다.
	 *
	 * @access protected
	 * @return void
	 * @param string $m 메시지
	 */
	protected function debug ($m) {
		if ( ! $this->verbose )
			return;

		echo 'DEBUG: ' . preg_replace ("/\r?\n$/", '', $m) . PHP_EOL;
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
