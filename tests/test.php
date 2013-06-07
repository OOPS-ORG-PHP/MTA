<?php
/*
 * Test code for pear_MTA
 * $Id$
 */

$iniget = function_exists ('___ini_get') ? '___ini_get' : 'ini_get';
$iniset = function_exists ('___ini_set') ? '___ini_set' : 'ini_set';

$cwd = getcwd ();
$ccwd = basename ($cwd);
if ( $ccwd == 'tests' ) {
	$oldpath = $iniget ('include_path');
	$newpath = preg_replace ("!/{$ccwd}!", '', $cwd);
	$iniset ('include_path', $newpath . ':' . $oldpath);
}

require_once 'MTA.php';


try {
	$o = (object) array (
		'from' => 'Sender <sender@domain.com>',
		'to'   => array (
			'Receiver1 <user1@domain1.com>',
			'Receiver2 <user1@domain2.com>',
			'Receiver3 <user2@domain1.com>',
			'Receiver4 <user2@domain2.com>',
		),
		'cc'   => 'CC User1 <ccuser@domain1.com>',
		'subject' => '보내보아요....',
		'body' => 'ㅎㅎㅎ <b>잘</b> 받아 보아요...',
		'attach' => array (
			'/usr/share/lighttpd/flags/kr.png',
			'/usr/share/lighttpd/flags/jp.png'
		)
	);

	$mta = new MTA ();

	echo ' ** test MTA::source method' . "\n\n";

	$template = $mta->source ($o);
	echo preg_replace ('/^/m', '    ', $template);

	echo "\n";
	echo ' ** test MTA::send method' . "\n\n";
	$mta->verbose = true;
	ob_start ();
	print_r ($mta->send ($o));
	$buf = ob_get_contents ();
	ob_end_clean ();

	echo preg_replace ('/^/m', '    ', $buf);
	echo "\n";

} catch ( myException $e ) {
	fprintf (STDERR, "%s\n", $e->Message ());
	#print_r ($e);
	#print_r ($e->Trace ());
	#echo $e->TraceAsString () . "\n";
	print_r ($e->TraceAsArray ()) . "\n";
	$e->finalize ();
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
