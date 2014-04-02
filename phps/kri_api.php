<?php
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

class KriApi {
	const HTTP_CLOSE = 1;
	const HTTP_KEEP_ALIVE = 2;
	const AGENT_BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:9.0.1) Gecko/20100101 Firefox/9.0.1';
	const AGENT_IBSHEET = 'IBLeaders IBSheet';
	const KCI = '0';
	const SCI = '2';
	const SCOPUS = '5';

	private $agcId;
	private $empNo;
	private $ip;
	private $returnUrl = 'http://xs.w.pw/result.php';

	private $host = "www.kri.go.kr";
	private $referer = "http://www.kri.go.kr";
	private $encodeUrl = '/kri/ra/cm/sso/wise_Encode.jsp';
	private $verifyUrl = '/kri/ra/cm/interface/interface_popup1js.jsp';


	public function __construct() {
		$this->ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
	}
	

	public function resetRandomIp() {
		$this->ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
	}


	public function setAuthParameters($agency_id, $user_no) {
		$this->agcId = $agency_id;
		$this->empNo = $user_no;
	}


	public function encodedRegNo() {
		$data = 'AgcId='.$this->agcId.'&RschrRegNo='.$this->empNo.'&returnUrl='.urlencode($this->returnUrl);
		$out = buildHttpRequest($data, $this->encodeUrl, KriApi::HTTP_CLOSE);

		$fp = fsockopen($this->host, 80, $errno, $errstr, 10);
		if (!$fp)
			return encodedRegNo();
		fwrite($fp, $out);
		$r_data = '';
		while(!feof($fp))
			$r_data .= fgets($fp, 128);
		fclose($fp);

		// Extract HTML sources from the packet.
		$pattern = "`(<[Hh][Tt][Mm][Ll]>)[[:ascii:]]*(<\/[Hh][Tt][Mm][Ll]>)`";
		preg_match($pattern, $r_data, $matches);

		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->loadHTML($matches[0]);
		$elements = $doc->getElementsByTagName('input');
		$Kri_rshcrRegNo = '';

		foreach($elements as $element)
			if ($element->getAttribute('name') == 'okri_param1')
				$Kri_rshcrRegNo = $element->getAttribute('value');

		return $Kri_rshcrRegNo;
	}


	public function buildHttpRequest($data, $uri, $conn = KriApi::HTTP_CLOSE, $agent = KriApi::AGENT_BROWSER) {
		$out ="POST ".$uri." HTTP/1.1\r\n";
		$out.="Host: ".$this->host."\r\n";
		$out.="Referer: ".$this->referer."\r\n";
		//$out.="Client-IP: ".$this->ip."\r\n";
		$out.="Accept: */*\r\n";
		$out.="Content-Type: application/x-www-form-urlencoded\r\n";
		$out.="User-Agent: ".$agent."\r\n";
		$out.="Accept-Language: en-us\r\n";
		$out.="Accept-Encoding: gzip, deflate\r\n";
		$out.="Content-Length: ".strlen($data)."\r\n";
		$out.="DNT: 1\r\n";
		$out.="Cache-Control: no-cache\r\n";
		if ($conn == KriApi::HTTP_CLOSE)
			$out.="Connection: close\r\n\r\n";
		else {
			//$out.="Keep-Alive: timeout=10, max=399\r\n";
			$out.="Connection: Keep-Alive\r\n\r\n";
		}
		$out.=$data;
		return $out;
	}


	private function readListData($researchTitle, $year, $journalLevel) {
		$data = '';
		if ($journalLevel == KriApi::KCI) {
			/* Data structure for KCI
			 * sheetAcation=R&txtRschrRegNm=&txtOrgLangPprNm=Robust+Carrier
			 * &txtScjnlNm=&txtKrfRegPblcYn=0&txtOvrsExclncScjnlPblcYn=0
			 * &txtPblcYm=2010&txtAgcID=131040&txtEmpNO=10206181&iPageNo=1
			 */
			$data .= 'sheetAcation=R&txtRschrRegNm=&txtOrgLangPprNm='.urlencode($researchTitle);
			$data .= '&txtScjnlNm=&txtKrfRegPblcYn=0&txtOvrsExclncScjnlPblcYn=0';
			$data .= '&txtPblcYm='.$year.'&txtAgcID='.$this->agcId.'&txtEmpNO='.$this->empNo.'&iPageNo=1';
		} else if ($journalLevel == KriApi::SCI || $journalLevel == KriApi::SCOPUS) {
			/* Data structure for SCI and Scopus
			 * sheetAcation=R2&iPageNo=1&txtRschrRegNm=
			 * &txtOrgLangPprNm=Robust+Carrier&txtScjnlNm=&txtKrfRegPblcYn=0
			 * &txtOvrsExclncScjnlPblcYn=2&txtPblcYm=2010&txtAgcID=131040&txtEmpNO=10206181
			 */
			$data .= 'sheetAcation=R2&iPageNo=1&txtRschrRegNm=';
			$data .= '&txtOrgLangPprNm='.urlencode($researchTitle).'&txtScjnlNm=&txtKrfRegPblcYn=0';
			$data .= '&txtOvrsExclncScjnlPblcYn='.$journalLevel;
			$data .= '&txtPblcYm='.$year.'&txtAgcID='.$this->agcId.'&txtEmpNO='.$this->empNo;	
		}

		$out = $this->buildHttpRequest($data, $this->verifyUrl, KriApi::HTTP_KEEP_ALIVE, KriApi::AGENT_IBSHEET);
		$fp = fsockopen($this->host, 80, $errno, $errstr, 10);
		if (!$fp) {
			echo date("Y-m-d H:i:s")." !! Failed to create a socket $errno $errstr".PHP_EOL;
			$this->resetRandomIp();
			return $this->readListData($researchTitle, $year, $journalLevel);
		}
		fwrite($fp, $out);
		fflush($fp);
		return $fp;
	}


	private function readDetailData($source, $yyyymm, $key, $journalLevel, $fp) {
		$data = '';
		if ($journalLevel == KriApi::KCI) {
			/* Data structure for KCI
			 * sheetAcation=R1&txtKeyValue=000283943700009&txtPblcYm=201008
			 * &txtKrfRegPblcYn=0&txtAgcID=131040&txtEmpNO=10206181
			 * &txtOvrsExclncScjnlPblcYn=0&txtScrDvsCd=KCI
			 */
			$data .= 'sheetAcation=R1&txtKeyValue='.$key.'&txtPblcYm='.$yyyymm;
			$data .= '&txtKrfRegPblcYn=0&txtAgcID='.$this->agcId.'&txtEmpNO='.$this->empNo;
			$data .= '&txtOvrsExclncScjnlPblcYn='.$journalLevel.'&txtScrDvsCd='.$source;
		} else if ($journalLevel == KriApi::SCI || $journalLevel == KriApi::SCOPUS) {
			/* Data structure for SCI and Scopus
			 * sheetAcation=R3&txtKeyValue=1&txtPblcYm=201012
			 * &txtKrfRegPblcYn=0&txtAgcID=131040&txtEmpNO=10206181
			 * &txtOvrsExclncScjnlPblcYn=2&txtScrDvsCd=SCI
			 */
			$data .= 'sheetAcation=R3&txtKeyValue='.$key.'&txtPblcYm='.$yyyymm;
			$data .= '&txtKrfRegPblcYn=0&txtAgcID='.$this->agcId.'&txtEmpNO='.$this->empNo;
			$data .= '&txtOvrsExclncScjnlPblcYn='.$journalLevel.'&txtScrDvsCd='.$source;
		}

		$out = $this->buildHttpRequest($data, $this->verifyUrl, KriApi::HTTP_CLOSE, KriApi::AGENT_IBSHEET);
		fwrite($fp, $out);
		fflush($fp);
		return $fp;
	}


	public function saveXml($data) {
		$pattern = '`(?<=Content-Length: )[0-9]+`';
		preg_match_all($pattern, $data, $matches);

		$max_len = intval($matches[0][0]);

		$pattern = "/(<\?xml version='1.0'\s*\?>)[[:ascii:]\p{L}\p{Mn}\p{Pd}가-힣]{0,".$max_len."}(<\/[A-Z]+>)\s*$/";
		preg_match_all($pattern, $data, $matches);
		$pattern = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';
		return preg_replace($pattern, '', $matches[0][0]);
	}


	public function saveFirstXml($data) {
		$pattern = '`(?<=Content-Length: )[0-9]+`';
		preg_match_all($pattern, $data, $matches);

		$max_len = intval($matches[0][0]);

		$pattern = "#(<\?xml version='1.0'\s*\?>)[[:ascii:]\p{L}\p{Mn}\p{Pd}가-힣]{0,".$max_len."}(</[A-Z]+>)\s*(?=HTTP/1.1 200 OK)#";
		preg_match_all($pattern, $data, $matches);
		$pattern = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';
		return preg_replace($pattern, '', $matches[0][0]);
	}


	public function saveSecondXml($data) {
		$pattern = '`(?<=Content-Length: )[0-9]+`';
		preg_match_all($pattern, $data, $matches);

		$max_len = intval($matches[0][1]);

		$pattern = "#(<\?xml version='1.0'\s*\?>)[[:ascii:]\p{L}\p{Mn}\p{Pd}가-힣]{0,".$max_len."}(</[A-Z]+>)\s*$#";
		preg_match_all($pattern, $data, $matches);
		$pattern = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';
		return preg_replace($pattern, '', $matches[0][0]);
	}


	public static function convertTitle($text) {
		$keywords = preg_split("/[\s,]+/", $text);
		$merged_text = '';

		foreach ($keywords as $keyword) {
			$pattern = "`[가-힣a-zA-Z0-9\-]{2,}`";
			preg_match($pattern, $keyword, $matches);
			if (count($matches) > 0 &&
				$keyword == $matches[0]) {
				$merged_text .= ' '.$keyword;
			}
		}
		return $merged_text;
	}


	public function verify($researchTitle, $year, $journalLevel, $try = 1) {
		if ($try > 4) {
			/*
			if ($year < date('Y')) {
				$year = intval($year)+1;
				echo date("Y-m-d H:i:s") . " ** 연도 변경 ($year)" . PHP_EOL;
				return $this->verify($researchTitle, $year, $journalLevel, 1);
			} else {
				return array();
			}
			*/
			return array();
		}

		echo date("Y-m-d H:i:s") . " ** 시도 $try ($year, $journalLevel) $researchTitle" . PHP_EOL;
		$title = KriApi::convertTitle($researchTitle);
		
		// Request for a study list
		$fp = $this->readListData($title, $year, $journalLevel);
		echo date("Y-m-d H:i:s") . " -- 목록 HTTP 요청" . PHP_EOL;
		$r_data = '';
		while(!feof($fp))
			$r_data .= fgets($fp, 4096);
		fclose($fp);

		$xml = $this->saveXml($r_data);
		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$list_arr = array();
		$detail_arr = array();

		if ($doc->getElementsByTagName('DATA')->length > 0) {
			$cnt = $doc->getElementsByTagName('DATA')->item(0)->getAttribute('TOTAL');
			if ($cnt > 0) {
				$elements = $doc->getElementsByTagName('TD');
				foreach ($elements as $key => $value)
					$list_arr[$key] = $value->textContent;
				echo date("Y-m-d H:i:s") . " -- 목록조회 성공 " .$list_arr[2]. PHP_EOL;

				// Request for a study detail
				$fp = $this->readListData($title, $year, $journalLevel);
				$fp = $this->readDetailData($list_arr[1], $list_arr[5], $list_arr[6], $journalLevel, $fp);
				echo date("Y-m-d H:i:s") . " -- 상세정보 HTTP 요청" . PHP_EOL;
				$r_data = '';
				while(!feof($fp))
					$r_data .= fgets($fp, 4096);
				fclose($fp);

				$xml = $this->saveSecondXml($r_data);
				$doc = new DOMDocument();
				$doc->loadXML($xml);

				if ($doc->getElementsByTagName('ERROR')->length == 0) {
					$elements = $doc->getElementsByTagName('TD');
					foreach ($elements as $key => $value)
						$detail_arr[$key] = $value->textContent;

					// Sometimes it's different detail's title from that of list.
					if (trim($list_arr[2]) != trim($detail_arr[0])) {
						echo date("Y-m-d H:i:s") . " !! 상세조회 정보 상이 " . $detail_arr[0] . PHP_EOL;
						return $this->verify($researchTitle, $year, $journalLevel, 1);
					}

					preg_match_all("#[0-9xX]{4}#", $detail_arr[9], $matches);
					if (count($matches[0]) == 2)
						$detail_arr[9] = $matches[0][0].'-'.$matches[0][1];

					if ($cnt > 1)
						$detail_arr[17] = "추가 검증 필요";
					else
						$detail_arr[17] = "";
					echo date("Y-m-d H:i:s") . " ** 상세조회 성공 " . $detail_arr[0] . PHP_EOL;
				} else {
					$message = $doc->getElementsByTagName('MESSAGE')->item(0)->textContent;
					echo date("Y-m-d H:i:s") . " !! $message" . PHP_EOL;

					if (strpos($message, "시스템 과부하") !== false) {
						sleep(30);
					} else if (strpos($message, "목록 카운트를") !== false) {
						return array();
					} else if (strpos($message, "목록 총갯수") !== false) {
						$try = 1;
					} else {
						$try++;
					}
					
					return $this->verify($researchTitle, $year, $journalLevel, $try);
				}
			} else {
				echo date("Y-m-d H:i:s") . " -- 목록 결과 없음" . PHP_EOL;
				return $this->verify($researchTitle, $year, $journalLevel, ++$try);
			}
		} else if ($doc->getElementsByTagName('ERROR')->length > 0) {
			$message = $doc->getElementsByTagName('MESSAGE')->item(0)->textContent;
			echo date("Y-m-d H:i:s") . " !! $message" . PHP_EOL;

			if (strpos($message, "시스템 과부하") !== false) {
				sleep(30);
			} else if (strpos($message, "목록 카운트를") !== false) {
				return array();
			} else if (strpos($message, "목록 총갯수") !== false) {
				$try = 1;
			} else {
				$try++;
			}

			return $this->verify($researchTitle, $year, $journalLevel, $try);
		} else {
			echo date("Y-m-d H:i:s") . " !! DATA 태그 정보 없음" . PHP_EOL;
		}

		return $detail_arr;
	}


	public function getRawData($researchTitle, $year, $journalLevel) {
		$title = KriApi::convertTitle($researchTitle);
		
		// Request for a study list
		$fp = $this->readListData($title, $year, $journalLevel);
		$r_data = '';
		while(!feof($fp))
			$r_data .= fgets($fp, 4096);
		fclose($fp);

		$xml = $this->saveXml($r_data);
		$doc = new DOMDocument();
		$doc->loadXML($xml);

		if ($doc->getElementsByTagName('DATA')->length > 0) {
			$cnt = $doc->getElementsByTagName('DATA')->item(0)->getAttribute('TOTAL');
			if ($cnt > 0) {
				$elements = $doc->getElementsByTagName('TD');
				foreach ($elements as $key => $value)
					$list_arr[$key] = $value->textContent;

				// Request for a study detail
				$fp = $this->readListData($title, $year, $journalLevel);
				$fp = $this->readDetailData($list_arr[1], $list_arr[5], $list_arr[6], $journalLevel, $fp);
				$r_data = '';
				while(!feof($fp))
					$r_data .= fgets($fp, 4096);
				fclose($fp);

				return $r_data;
			}
		}
	}
}
?>