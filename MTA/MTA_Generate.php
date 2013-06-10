<?php
/**
 * Project: MTA_Generate :: generating mail section abstraction layer
 * File:    MTA/MTA_Generate.php
 *
 * MTA_Generate class는 메일 발송 내용을 구성하기 위한 추상
 * 레이어를 제공한다.
 *
 * @category   Networking
 * @package    MTA
 * @subpackage MTA_Generate
 * @author     JoungKyun.Kim <http://oops.org>
 * @copyright  (c) 2013, JoungKyun.Kim
 * @license    BSD License
 * @version    SVN: $Id$
 * @link       http://pear.oops.org/package/MTA
 * @filesource
 */

/**
 * import MTA_Socket class
 */
require_once 'MTA/MTA_Socket.php';

/**
 * 메일 발송 내용을 구성하기 위한 추상 레이어
 *
 * @package MTA
 */
Class MTA_Generate extends MTA_Socket {
	// {{{ properties
	/**#@+
	 * @access protected
	 */
	/**
	 * global top level domain
	 * @var array
	 */
	protected $gTLD = array (
		'com', 'net', 'org', 'edu', 'gov', 'mil',
		'aero', 'asia', 'biz', 'coop', 'info', 'int',
		'jobs', 'museum', 'name', 'pro', 'trabel',
		'cat', 'mobi', 'post', 'tel', 'xxx', 'arpa'
	);

	/**
	 * country code top level domain
	 * @var array
	 */
	protected $ccTLD = array (
		'ac', 'ad', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao',
		'aq', 'ar', 'as', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb',
		'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'bj', 'bl', 'bm', 'bn',
		'bo', 'bq', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca',
		'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn',
		'co', 'cr', 'cu', 'cv', 'cw', 'cx', 'cy', 'cz', 'de', 'dj',
		'dk', 'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'eh', 'er', 'es',
		'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb',
		'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp',
		'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn',
		'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq',
		'ir', 'is', 'it', 'je', 'jm', 'jo', 'jp', 'ke', 'kg', 'kh',
		'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb',
		'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma',
		'mc', 'md', 'me', 'mf', 'mg', 'mh', 'mk', 'ml', 'mm', 'mn',
		'mo', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'mv', 'mw', 'mx',
		'my', 'mz', 'na', 'nc', 'ne', 'nf', 'ng', 'ni', 'nl', 'no',
		'np', 'nr', 'nu', 'nz', 'om', 'pa', 'pe', 'pf', 'pg', 'ph',
		'pk', 'pl', 'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa',
		're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se',
		'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr',
		'ss', 'st', 'su', 'sv', 'sx', 'sy', 'sz', 'tc', 'td', 'tf',
		'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr',
		'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'um', 'us', 'uy',
		'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws',
		'ye', 'yt', 'za', 'zm', 'zw', '한국', '中国', '中國',
		'香港', '台湾', '台灣', '新加坡', 'テスト',
	);
	/**#@-*/
	// }}}

	// {{{ (string) public MTA_Generate::boundary (void)
	/**
	 * mail boundary 생성
	 *
	 * @access public
	 * @return string
	 */
	public function boundary () {
		$uc = uniqid ();
		$one = strtoupper ($uc[3] . $uc[1]. $uc[0]);
		$two = substr ($uc, 1, 8);
		$three = substr ($uc, -8);

		return sprintf (
			"--=_NextPart_000_0%s_%s.%s",
			$one, $two, $three
		);
	}
	// }}}

	// {{{ (string) public MTA_Generate::msgid (void)
	/**
	 * Message ID 생성
	 *
	 * @access public
	 * @return string
	 */
	public function msgid () {
		return '<' . date ('YmdHis') . rand () . '@' . gethostname () . '>';
	}
	// }}}

	// {{{ (string) public MTA_Generate::date (void)
	/**
	 * 메일 헤더에서 사용할 시간 형식 반환
	 *
	 * @access public
	 * @return string
	 */
	public function date () {
		return date ('D, d M Y H:i:s O');
	}
	// }}}

	// {{{ (string) public MTA_Generate::encode ($msg, $split = false)
	/**
	 * 메시지 인코딩
	 *
	 * $split이 false(기본값)로 설정될 경우 다음의 형식으로 반환
	 *   =?UTF-8?B?BASE64_문자열?=
	 *
	 * $split이 true로 설정될 경우, 76컬럼에서 line-break된
	 * base64 문자열 반환 (메일 본문 또는 첨부파일 인코딩)
	 *
	 * @access public
	 * @return string
	 * @param string $msg
	 * @param bool $split true로 설정이 되면 76칼럼에서 line-break된
	 *                    base64 문자열을 반환한다.
	 */
	public function encode ($msg, $split = false) {
		if ( $split )
			return chunk_split (base64_encode ($msg), 76, "\r\n");

		return '=?UTF-8?B?' . base64_encode ($msg) . '?=';
	}
	// }}}

	// {{{ public MTA_Generate::addr (&$addr)
	/**
	 * Check mail address and convert email format
	 *
	 * @access private
	 * @return bool 에러 발생시, myException으로 에러 메시지를 보낸다.
	 * @param  string $addr 검사할 메일 주소. 지원 형식은 다음과 같다.
	 *                      user@domain.com
	 *                      &lt;user@domain.com&gt;
	 *                      이름 &lt;user@domain.com&gt;
	 */
	public function addr (&$addr) {
		$addr = trim ($addr);

		if ( preg_match ('/^([^<]+)<([^@]+)@([^>]+)>$/', $addr, $matches) ) {
			$e = (object) array (
				'name' => preg_replace ('/^[\'"]|[\'"]$/', '', trim ($matches[1])),
				'user' => ltrim ($matches[2]),
				'domain' => rtrim ($matches[3])
			);
		} else if ( preg_match ('/^<?([^@<]+)@([^>]+)>?$/', $addr, $matches) ) {
			$e = (object) array (
				'name' => '',
				'user' => ltrim ($matches[1]),
				'domain' => rtrim ($matches[2])
			);
		} else {
			if ( ! strlen ($addr) )
				$this->error ('Given address is NULL', E_USER_ERROR);

			$this->error ('Invalid email address format', E_USER_ERROR);
		}

		if ( $e->name ) {
			$e->name = '"' . $e->name . '"';
			if ( preg_match ('/[^a-z0-9"\'|:;{}\[\]()!#$%&*+_=~., -]/i', $e->name) )
				$e->name = $this->encode ($e->name);
			$e->name .= ' ';
		}

		$this->check_local ($e->user);
		$this->check_domain ($e->domain);

		$addr = sprintf ('%s<%s@%s>', $e->name, trim ($e->user), ($e->domain));

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Generate::check_local ($user, $method = false)
	/**
	 * 메일 주소의 local 섹션의 유효성을 검증한다.
	 *
	 * @access protected
	 * @return bool 에러 발생시에, myException으로 에러를 보낸다.
	 * @param  string $user 검사할 메일 주소의 local 섹션값
	 * @param  bool   $method true로 설정하면, 에러 발생시에 false를 반환
	 */
	protected function check_local ($user, $method = false) {
		if ( $user[0] == '"' && $user[strlen ($user) - 1] == '"' ) {
			$src = array ('/[ ]/', '/^"|"$/');
			$dst = array ('+++blank+++', '');
			$user = preg_replace ($src, $dst, $user);
		}

		if ( preg_match ('/[^a-z0-9.+]/i', $user) ) {
			if ( $method )
				return false;

			$this->error ('Invalid local part of email address', E_USER_WARNING);
		}

		return true;
	}
	// }}}

	// {{{ (bool) protected MTA_Generate::check_domain ($domain, $method = false)
	/**
	 * Check domain of mail address
	 * 메일 주소의 도메인 섹션의 유효성을 검증한다.
	 *
	 * @access protected
	 * @return bool
	 * @param  string $domain 검사할 메일 주소의 도메인 섹션
	 * @param  bool   $method true로 설정하면, 에러 발생시에 false를 반환
	 */
	protected function check_domain ($domain, $method = false) {
		$domlen = strlen ($domain);

		if ( $domlen > 253 )
			$this->error ('Invalid length of domain part (max 253)', E_USER_ERROR);

		// If domain part don't have dot character,
		// permit only top-level domain or local hostname
		if ( ! preg_match ('/\./', $domain ) ) {
			if ( array_search ($domain, $this->gTLD) !== false )
				return true;

			if ( array_search ($domain, $this->ccTLD) !== false )
				return true;

			if ( gethostbyname ($domain) !== false )
				return true;

			if ( ! $this->method ) {
				$this->error (
					sprintf ('%s is not gTLD or ccTLD or local hostname', $domain),
					E_USER_ERROR
				);
			}

			return false;
		}

		// check IPv4 or IPv6 format
		if ( $domain[0] == '[' && $domain[$domlen - 1] == ']' ) {
			// check for IPv4
			if ( preg_match (
					'/^\[([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\]$/',
					$domain, $matches
				)
			) {
				foreach ($matches as $key => $val ) {
					if ( $key == 0 )
						continue;

					if ( $val > 255 ) {
						if ( ! $this->method ) {
							$this->error (
								sprintf ('%s is not invalid IPv4 format', $domain),
								E_USER_ERROR
							);
						}

						return false;
					}
				}

				return true;
			}

			// check for IPv6
			if ( preg_match ('/^\[IPV6:[0-9a-z:]+\]$/', $domain) )
				return true;

			if ( ! $this->method ) {
				$this->error (
					sprintf ('%s is invliad email address format', $domain),
					E_USER_ERROR
				);
			}

			return false;
		}

		// check General domain format
		if ( preg_match ('/\.\.+/', $domain) ) {
			if ( ! $this->method )
				$this->error ('Invalid domain part', E_USER_ERROR);

			return false;
		}

		$tld = preg_replace ('/.*\.([^.]+)$/', '\\1', $domain);

		if ( array_search ($tld, $this->gTLD) === false &&
			array_search ($tld, $this->ccTLD) === false ) {

			if ( ! $this->method )
				$this->error ('Invliad TLD', E_USER_ERROR);

			return false;
		}

		return true;
	}
	// }}}

	// {{{ (string) public MTA_Generate::mime ($path)
	/**
	 * 파일 확장자에 대한 mime type을 반환
	 *
	 * fileinfo extension이 제공되면 fileinfo api를 사용하며,
	 * 지원하지 않을경우 자체 정의된 mime-type을 반환한다.
	 *
	 * @access public
	 * @return string
	 * @param string $path 검사할 파일 경로
	 */
	public function mime ($path) {
		if ( function_exists ('finfo_open') ) {
			$fi = finfo_open (FILEINFO_MIME_TYPE);
			$mime = finfo_file ($fi, $path);
			finfo_close ($fi);

			return $mime;
		}

		if ( preg_match ('^/.+\.([^.]+)$/', $path, $matches) ) {
			$path = $matches[1];
		} else
			return 'application/octet-stream';

		if ( $path == 'ez'  ) return 'application/andrew-inset';
		else if ( $path == 'hqx' ) return 'application/mac-binhex40';
		else if ( $path == 'cpt' ) return 'application/mac-compactpro';
		else if ( $path == 'doc' ) return 'application/msword';
		else if ( $path == 'oda' ) return 'application/oda';
		else if ( $path == 'pdf' ) return 'application/pdf';
		else if ( $path == 'rtf' ) return 'application/rtf';
		else if ( $path == 'mif' ) return 'application/vnd.mif';
		else if ( $path == 'ppt' ) return 'application/vnd.ms-powerpoint';
		else if ( $path == 'slc' ) return 'application/vnd.wap.slc';
		else if ( $path == 'sic' ) return 'application/vnd.wap.sic';
		else if ( $path == 'wmlc' ) return 'application/vnd.wap.wmlc';
		else if ( $path == 'wmlsc' ) return 'application/vnd.wap.wmlscriptc';
		else if ( $path == 'bcpio' ) return 'application/x-bcpio';
		else if ( $path == 'bz2' ) return 'application/x-bzip2';
		else if ( $path == 'vcd' ) return 'application/x-cdlink';
		else if ( $path == 'pgn' ) return 'application/x-chess-pgn';
		else if ( $path == 'cpio' ) return 'application/x-cpio';
		else if ( $path == 'csh' ) return 'application/x-csh';
		else if ( $path == 'dvi' ) return 'application/x-dvi';
		else if ( $path == 'spl' ) return 'application/x-futuresplash';
		else if ( $path == 'gtar' ) return 'application/x-gtar';
		else if ( $path == 'hdf' ) return 'application/x-hdf';
		else if ( $path == 'js' ) return 'application/x-javascript';
		else if ( $path == 'ksp' ) return 'application/x-kspread';
		else if ( $path == 'kpr' || $path == 'kpt' ) return 'application/x-kpresenter';
		else if ( $path == 'chrt' ) return 'application/x-kchart';
		else if ( $path == 'kil' ) return 'application/x-killustrator';
		else if ( $path == 'skp' || $path == 'skd' || $path == 'skt' || $path == 'skm' )
			return 'application/x-koan';
		else if ( $path == 'latex' ) return 'application/x-latex';
		else if ( $path == 'nc' || $path == 'cdf' ) return 'application/x-netcdf';
		else if ( $path == 'rpm' ) return 'application/x-rpm';
		else if ( $path == 'sh' ) return 'application/x-sh';
		else if ( $path == 'shar' ) return 'application/x-shar';
		else if ( $path == 'swf' ) return 'application/x-shockwave-flash';
		else if ( $path == 'sit' ) return 'application/x-stuffit';
		else if ( $path == 'sv4cpio' ) return 'application/x-sv4cpio';
		else if ( $path == 'sv4crc' ) return 'application/x-sv4crc';
		else if ( $path == 'tar' ) return 'application/x-tar';
		else if ( $path == 'tcl' ) return 'application/x-tcl';
		else if ( $path == 'tex' ) return 'application/x-tex';
		else if ( $path == 'texinfo' || $path == 'texi' ) return 'application/x-texinfo';
		else if ( $path == 't' || $path == 'tr' || $path == 'roff' )
			return 'application/x-troff';
		else if ( $path == 'man' ) return 'application/x-troff-man';
		else if ( $path == 'me' ) return 'application/x-troff-me';
		else if ( $path == 'ms' ) return 'application/x-troff-ms';
		else if ( $path == 'ustar' ) return 'application/x-ustar';
		else if ( $path == 'src' ) return 'application/x-wais-source';
		else if ( $path == 'zip' ) return 'application/zip';
		else if ( $path == 'gif' ) return 'image/gif';
		else if ( $path == 'ief' ) return 'image/ief';
		else if ( $path == 'wbmp' ) return 'image/vnd.wap.wbmp';
		else if ( $path == 'ras' ) return 'image/x-cmu-raster';
		else if ( $path == 'pnm' ) return 'image/x-portable-anymap';
		else if ( $path == 'pbm' ) return 'image/x-portable-bitmap';
		else if ( $path == 'pgm' ) return 'image/x-portable-graymap';
		else if ( $path == 'ppm' ) return 'image/x-portable-pixmap';
		else if ( $path == 'rgb' ) return 'image/x-rgb';
		else if ( $path == 'xbm' ) return 'image/x-xbitmap';
		else if ( $path == 'xpm' ) return 'image/x-xpixmap';
		else if ( $path == 'xwd' ) return 'image/x-xwindowdump';
		else if ( $path == 'css' ) return 't$path/css';
		else if ( $path == 'rtx' ) return 't$path/richt$path';
		else if ( $path == 'rtf' ) return 't$path/rtf';
		else if ( $path == 'tsv' ) return 't$path/tab-separated-values';
		else if ( $path == 'sl' ) return 't$path/vnd.wap.sl';
		else if ( $path == 'si' ) return 't$path/vnd.wap.si';
		else if ( $path == 'wml' ) return 't$path/vnd.wap.wml';
		else if ( $path == 'wmls' ) return 't$path/vnd.wap.wmlscript';
		else if ( $path == 'etx' ) return 't$path/x-set$path';
		else if ( $path == 'xml' ) return 't$path/xml';
		else if ( $path == 'avi' ) return 'video/x-msvideo';
		else if ( $path == 'movie' ) return 'video/x-sgi-movie';
		else if ( $path == 'wma' ) return 'audio/x-ms-wma';
		else if ( $path == 'wax' ) return 'audio/x-ms-wax';
		else if ( $path == 'wmv' ) return 'video/x-ms-wmv';
		else if ( $path == 'wvx' ) return 'video/x-ms-wvx';
		else if ( $path == 'wm' ) return 'video/x-ms-wm';
		else if ( $path == 'wmx' ) return 'video/x-ms-wmx';
		else if ( $path == 'wmz' ) return 'application/x-ms-wmz';
		else if ( $path == 'wmd' ) return 'application/x-ms-wmd';
		else if ( $path == 'ice' ) return 'x-conference/x-cooltalk';
		else if ( $path == 'ra' ) return 'audio/x-realaudio';
		else if ( $path == 'wav' ) return 'audio/x-wav';
		else if ( $path == 'png' ) return 'image/png';
		else if ( $path == 'asf' || $path == 'asx' ) return 'video/x-ms-asf';
		else if ( $path == 'html' || $path == 'htm' ) return 't$path/html';
		else if ( $path == 'smi' || $path == 'smil' ) return 'application/smil';
		else if ( $path == 'gz' || $path == 'tgz' ) return 'application/x-gzip';
		else if ( $path == 'kwd' || $path == 'kwt' ) return 'application/x-kword';
		else if ( $path == 'kpr' || $path == 'kpt' ) return 'application/x-kpresenter';
		else if ( $path == 'au' || $path == 'snd' ) return 'audio/basic';
		else if ( $path == 'ram' || $path == 'rm' ) return 'audio/x-pn-realaudio';
		else if ( $path == 'pdb' || $path == 'xyz' ) return 'chemical/x-pdb';
		else if ( $path == 'tiff' || $path == 'tif' ) return 'image/tiff';
		else if ( $path == 'igs' || $path == 'iges' ) return 'model/iges';
		else if ( $path == 'wrl' || $path == 'vrml' ) return 'model/vrml';
		else if ( $path == 'asc' || $path == 'txt' || $path == 'php' ) return 't$path/plain';
		else if ( $path == 'sgml' || $path == 'sgm' ) return 't$path/sgml';
		else if ( $path == 'qt' || $path == 'mov' ) return 'video/quicktime';
		else if ( $path == 'ai' || $path == 'eps' || $path == 'ps' ) return 'application/postscript';
		else if ( $path == 'dcr' || $path == 'dir' || $path == 'dxr' ) return 'application/x-director';
		else if ( $path == 'mid' || $path == 'midi' || $path == 'kar' ) return 'audio/midi';
		else if ( $path == 'mpga' || $path == 'mp2' || $path == 'mp3' ) return 'audio/mpeg';
		else if ( $path == 'aif' || $path == 'aiff' || $path == 'aifc' ) return 'audio/x-aiff';
		else if ( $path == 'jpeg' || $path == 'jpg' || $path == 'jpe' ) return 'image/jpeg';
		else if ( $path == 'msh' || $path == 'mesh' || $path == 'silo' ) return 'model/mesh';
		else if ( $path == 'mpeg' || $path == 'mpg' || $path == 'mpe' ) return 'video/mpeg';
		else return 'application/octet-stream';
	}
	// }}}

	// {{{ (string) public MTA_Generate::attach ($attaches, $bound)
	/**
	 * Attach file의 template을 생성
	 *
	 * @access protect
	 * @return string
	 * @param array attach 파일 경로를 포함한 배열
	 */
	public function attach ($attaches, $bound) {
		if ( ! is_array ($attaches) )
			return '';

		if ( count ($attaches) == 0 )
			return '';

		foreach ( $attaches as $path ) {
			if ( ! file_exists ($path) || ! is_readable ($path) )
				continue;

			$mime = $this->mime ($path);
			$fname = basename ($path);

			$pos = preg_match ('/^image\//', $mime) ?  'inline' : 'attachment';

			$buf .=
				'--' . $bound . "\r\n" .
				"Content-Type: {$mime}; name=\"{$fname}\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline; filename=\"{$fname}\"\r\n\r\n" .
				$this->encode (file_get_contents ($path), true) . "\r\n";
		}

		return $buf;
	}
	// }}}

	// {{{ (void) protected MTA_Generate::error ($msg, $level)
	/**
	 * 에러 발생시에, 에러 메시지를 myException으로 전달한다.
	 *
	 * @access protected
	 * @return void
	 * @param  string $msg   exception message
	 * @param  int    $level exception error level
	 */
	protected function error ($msg, $level) {
		throw new myException ($msg, $level);
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
