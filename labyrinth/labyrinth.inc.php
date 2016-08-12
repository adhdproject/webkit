<?php

/**
 * labyrinth.inc.php
 *
 * Functions for creating a web page with bogus links in order to entrap
 * web scanners.
 *
 * All code Copyright (c) 2010-2011, Ben Jackson and Mayhemic Labs - 
 * bbj@mayhemiclabs.com. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code mustu retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the author nor the names of contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
 
include_once('config.inc.php');
 
class Labyrinth {
	
	var $dbhandle;
	var $crawler_seen;
	var $crawler_ip;
	var $crawler_useragent;

	public $new;

	public function Labyrinth($ip,$useragent){
		global $config;
		mt_srand($this->MakeSeed());
		
		if(empty($config['db_username'])){
			$this->dbhandle = 
				new PDO($config['pdo_connection_string']);
		} else {
			$this->dbhandle = 
				new PDO($config['pdo_connection_string'], 
					$config['db_username'], $config['db_password']);
		}
		
		$this->crawler_ip = $this->dbhandle->quote($ip);
		$this->crawler_useragent = $this->dbhandle->quote($useragent);

		$rows = $this->dbhandle->query("SELECT COUNT(*) FROM crawlers WHERE crawler_ip=" 
			. $this->crawler_ip ." AND crawler_useragent=" 
			. $this->crawler_useragent . ";");
		if($rows->fetchColumn() != 0) {
			$this->crawler_seen = true;
		} else {
			$this->crawler_seen = false;
		}
	}

	function CheckForSearchEngines(){
		switch(true){
			case preg_match("/Google/",$this->crawler_useragent):
			case preg_match("/Yandex/",$this->crawler_useragent):
			case preg_match("/Openfind/",$this->crawler_useragent):
			case preg_match("/msnbot/",$this->crawler_useragent):
			case preg_match("/bingbot/",$this->crawler_useragent):
			case preg_match("/Slurp/",$this->crawler_useragent):
			case preg_match("/Yahoo/",$this->crawler_useragent):
			case preg_match("/Architext/",$this->crawler_useragent):
				return true;
				break;
		}
	}

	function MakeSeed(){
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 123456);
	}


	function SpinTheWheelOfErrors(){
		$error_chance = rand(0,100);

		$error_string = false;

		if ($error_chance == 16){
			$error_string = "HTTP/1.1 404 Not Found";
		}elseif ($error_chance == 23){
			$error_string = "HTTP/1.1 403 Forbidden";
		}elseif ($error_chance == 42){
			#Included just for the WTF Factor
			$error_string = "HTTP/1.1 402 Payment Required";
		}

		if ($error_string){
			header($error_string);
			exit;
		}
	}

	function GenerateAlert($message="We got a live one!"){
		global $config;

		//Have we seen this crawler recently?		
		$query = "SELECT last_alert FROM crawlers WHERE crawler_ip=" 
			. $this->crawler_ip . " AND crawler_useragent=" . $this->crawler_useragent . ";"; 
		$last_seen_query = $this->dbhandle->query($query)
			 or die(print_r($this->dbhandle->errorInfo(), true));
		if ($last_seen_query) {
			$last_alert_time = $last_seen_query->fetchColumn();
			$time = time() - $last_alert_time;
		}
		if (!$last_seen_query || ($time > 3600)){
			if ($config['alert_ids']['enabled']){
				print $config['alert_ids']['text'] . ' ';
			}

			if ($config['alert_email']['enabled']){
				mail($config['alert_email']['address'], "WebLabyrinth Alert - " 
					. $this->crawler_ip, "$message\n\nIP: "  . $this->crawler_ip 
					. "\nUser Agent: " . $this->crawler_useragent);
			}

			if ($config['alert_syslog']['enabled']){
				openlog("weblabyrinth", LOG_PID, LOG_LOCAL0);
				syslog(LOG_WARNING, "ALERT, message=[$message], 
					src_ip=[{$_SERVER['REMOTE_ADDR']}], 
					user_agent=[{$_SERVER['HTTP_USER_AGENT']}]");
				closelog();
			}

			$last_alert_query = $this->dbhandle->query("UPDATE crawlers SET last_alert=" 
				. time() . " WHERE crawler_ip=" . $this->crawler_ip 
				. " AND crawler_useragent=" . $this->crawler_useragent . ";")
			 	or die(print_r($this->dbhandle->errorInfo(), true));
		}
	}

	function LogCrawler(){
		global $config;

		if($this->crawler_seen) {
			$this->dbhandle->query("UPDATE crawlers SET last_seen = " . time() 
				. ", num_hits=num_hits+1 WHERE crawler_ip=" . $this->crawler_ip 
				. " AND crawler_useragent=" . $this->crawler_useragent . ";")
				or die(print_r($this->dbhandle->errorInfo(), true));
		} else {
			$crawler_rdns = gethostbyaddr($this->crawler_ip);
			$this->dbhandle->query("INSERT INTO crawlers(crawler_ip, "
				. "crawler_rdns, crawler_useragent, first_seen, last_seen, num_hits) "
				. "VALUES (" . $this->crawler_ip . ", "
				. $this->dbhandle->quote($crawler_rdns) . ", "
				. $this->crawler_useragent . ", "
				. time() . ", " . time() . ", 1);")
				or die(print_r($this->dbhandle->errorInfo(), true));
			$this->new = true;
			if($config['alert_on_new']){
				$this->GenerateAlert("New host logged!");
			}

			$this->crawler_seen = true;
		}
	}
}
?>


