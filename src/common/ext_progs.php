<?php

/**
 * LMS2
 *
 * @version $Id$
 * @copyright 2007
 */

class ExternalProgram {
	var $read_buffer='';
	var $pipes;
	var $process;

	function escapeshelloption($str)
	{
		return "%" . strlen($str) . "%" . $str;
	}

	function clear_buffer()
	{
		$this->read_buffer = "";
	}

	function receive_data($len = 2, $end = "> ", $maxlen = 0, $blocking = false)
	{
		stream_set_blocking($this->pipes[1], $blocking);
		while ($ret = fread($this->pipes[1], $len)) {
			$this->read_buffer .= $ret;
			if (($end && (substr_count($this->read_buffer, $end) > 0)) || ($maxlen && (strlen($this->read_buffer) > $maxlen))) break;
		}
		return $this->read_buffer;
	}

	function send_data($str)
	{
		fwrite($this->pipes[0], $str . "\n");
	}

	function request($str)
	{
		$this->clear_buffer();
		$this->send_data($str . "\n");
		$this->receive_data(2, null, 1, true);
		$this->receive_data(2, null, 0, false);
		$res = $this->read_buffer;
		$this->clear_buffer();
		return $res;
	}

	function start($str)
	{
		$descriptorspec = array(0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
			);
		$this->process = proc_open($str, $descriptorspec, $this->pipes);
		return is_resource($this->process);
	}

	function stop()
	{
		fclose($this->pipes[0]);
		fclose($this->pipes[1]);
		fclose($this->pipes[2]);
		return proc_close($this->process);
	}
}

?>