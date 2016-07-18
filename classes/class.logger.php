<?php 

class Logger {

	static function info( $message ) {
		// $metas = debug_backtrace();
		// $metas = $metas[0];
		// $log_message  = "\r\nFile: " . $metas['file'];
		// $log_message .= "\r\nLine: " . $metas['line'];
		// $log_message .= "\r\nTime: " . date('Y-m-d H:i:s');
		// $log_message .= "\r\nMessage: " . var_export( $message, true );
		// $log_message .= "\r\n-------------------\r\n";
		// $logsFile = AUSNG_DIR . '/logs/infos.txt';
		// $data = file_get_contents( $logsFile );
		// $data .= $log_message;
		// file_put_contents( $logsFile, $data );
	}

}