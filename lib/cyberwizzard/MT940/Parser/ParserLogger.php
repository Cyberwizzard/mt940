<?php

/*
 * This file is part of the Cyberwizzard\MT940 library - parser log class; parsers of 
 * the library use this class to provide logging for debugging purposes.
 *
 * Copyright (c) 2017 Berend Dekens <cyberwizzard@gmail.com>
 * Copyright (c) 2012 Sander Marechal <s.marechal@jejik.com>
 * Licensed under the MIT license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace cyberwizzard\MT940\Parser;

class ParserLogger {
	var $log_lvl = 0;
	var $direct_output = false;
	var $buffer_output = true;
	var $buffer = "";

	function setLogLevel($lvl) {
		$this->log_lvl = $lvl;
		return $this;
	}

	function getLogLevel() {
		return $this->log_lvl;
	}

	function getLogLevelName($lvl) {
		if($lvl == LOG_DEBUG)   return "DEBUG  ";
		if($lvl == LOG_INFO)    return "INFO   ";
		if($lvl == LOG_NOTICE)  return "NOTICE ";
		if($lvl == LOG_WARNING) return "WARNING";
		if($lvl == LOG_ERR)     return "ERROR  ";
		return                         "DEBUG  ";
	}

	function log($lvl, $msg) {
		if($lvl <= $this->log_lvl) {
			$line = sprintf("[%s] %s", $this->getLogLevelName($lvl), $msg);
			if($this->buffer_output)
				$this->buffer .= $line . "\n";
			if($this->direct_output)
				echo $line . "<br/>\n";
		}
		return $this;
	}

	function getBuffer() {
		return $this->buffer;
	}

	function clearBuffer() {
		$this->buffer = "";
		return $this;
	}

	function setDirectOutput($enable) {
		$this->direct_output = $enable === true;
		return $this;
	}

	function setBufferOutput($enable) {
		$this->buffer_output = $enable === true;
		return $this;
	}
}