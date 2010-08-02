<?php

/**
 * Debugging filter that saves the incoming and outgoing binary streams to files defined in proxy configuration options.
 * It accepts the following parameters:
 * - in_stream_file : name of the file for the incoming (reply) stream
 * - out_stream_file : name of the file for the outgoing (call) stream
 * - dump_detail : detail of the output, can be 'simple' or 'advanced'
 * - dump_mode : if this is set to 'save' every call will generate a new file, if set to 'append' the output will be
 * appended to the file
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_StreamDumpFilter extends Hessian_InterceptingFilter{
	function execute(&$context,&$chain){
		$chain->doFilter($context);
		$options = $context->options;
		$inFile = @$options['in_stream_file'];
		$outFile = @$options['out_stream_file'];

		if($inFile) {
			$this->saveFile($inFile,$context,'in');
		}
		if($outFile)
			$this->saveFile($outFile,$context,'out');
	}

	function saveFile($file,&$context,$type){
		$stream = '';
		if($type=='in')
			$stream = $context->parser->stream;
		else
			$stream = $context->writer->stream;
		
		$detail = @$context->options['dump_detail'];
		$mode = @$context->options['dump_mode'];
		if(!in_array($mode,array('save','append') ) )
			$mode = 'save';
		if(!in_array($detail,array('simple','advanced') ) )
			$detail = 'simple';
		
		$data = '';
		if($detail == 'advanced'){
			if($type=='in')
				$data = "INCOMING payload for URL: ".$context->url."\n";
			else
				$data = "\nOUTGOING payload for URL: ".$context->url."\n";
			$data .= "Method: ".$context->callingContext['method']."\n";
			$data .= "Time: ".date("Y-m-d H:i:s")."\nData:\n";
			$data .= $stream;
			$data .= "\n\n";
		} else
			$data .= $stream;
		if($mode == 'save')
			$handle = fopen($file, 'w+');
		else
			$handle = fopen($file, 'a+');

		fwrite($handle, $data);
		fclose($handle);
	}

}