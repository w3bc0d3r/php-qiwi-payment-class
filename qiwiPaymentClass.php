<?php
	class qiwiPaymentClass
	{
		public function __construct ()
	        {
			$this -> curl = curl_init ();
			$this -> fileCookies = 'cookies.txt';
			$this -> ticket = '';
		}
		public function auth ($login, $password)
		{
			curl_setopt ($this -> curl, CURLOPT_URL, 'https://sso.qiwi.com/cas/tgts');
			curl_setopt ($this -> curl, CURLOPT_HEADER, 0);
			curl_setopt ($this -> curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($this -> curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt ($this -> curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($this -> curl, CURLOPT_COOKIEFILE, $this -> fileCookies);
			curl_setopt ($this -> curl, CURLOPT_COOKIEJAR, $this -> fileCookies);
			curl_setopt ($this -> curl, CURLOPT_POST, 1);
			curl_setopt ($this -> curl, CURLOPT_POSTFIELDS, '{"login":"'.$login.'","password":"'.$password.'"}');
			curl_setopt ($this -> curl, CURLOPT_HTTPHEADER,
				array (
					'User-Agent Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0',
					'Accept: application/vnd.qiwi.sso-v1+json',
					'Accept-Language: ru;q=0.8,en-US;q=0.6,en;q=0.4',
					'Accept-Encoding: gzip, deflate',
					'Content-Type: application/json; charset=UTF-8',
					'Referer: https://qiwi.com/',
					'Origin: https://qiwi.com',
					'Connection: keep-alive',
					'Pragma: no-cache',
					'Cache-Control: no-cache'
				)
			);
			$cont = curl_exec ($this -> curl);
			$jsonCont = json_decode ($cont);
			if (!isset ($jsonCont -> entity -> ticket))
			{
				return 0;
			}
			$this -> ticket = $jsonCont -> entity -> ticket;
			curl_setopt ($this -> curl, CURLOPT_URL, 'https://sso.qiwi.com/cas/sts');
			curl_setopt ($this -> curl, CURLOPT_POSTFIELDS, '{"ticket":"'.$this -> ticket.'","service":"https://qiwi.com/j_spring_cas_security_check"}');
			curl_setopt ($this -> curl, CURLOPT_HTTPHEADER,
				array (
					'User-Agent Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0',
					'Accept: application/vnd.qiwi.sso-v1+json',
					'Accept-Language: ru;q=0.8,en-US;q=0.6,en;q=0.4',
					'Accept-Encoding: deflate',
					'Content-Type: application/json; charset=UTF-8',
					'Referer: https://sso.qiwi.com/app/proxy?v=1',
					'Connection: keep-alive',
					'Pragma: no-cache',
					'Cache-Control: no-cache'
				)
			);
			$cont = curl_exec ($this -> curl);
			$jsonCont = json_decode ($cont);
			if (!isset ($jsonCont -> entity -> ticket))
			{
				return 0;
			}
			$this -> ticket = $jsonCont -> entity -> ticket;
			curl_setopt ($this -> curl, CURLOPT_URL, 'https://qiwi.com/j_spring_cas_security_check?ticket='.$this -> ticket);
			curl_setopt ($this -> curl, CURLOPT_POST, 0);
			curl_setopt ($this -> curl, CURLOPT_HTTPHEADER,
				array (
					'User-Agent Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0',
					'Accept: application/json, text/javascript, */*; q=0.01',
					'Accept-Language: en-US,en;q=0.5',
					'Accept-Encoding: deflate',
					'X-Requested-With: XMLHttpRequest',
					'Referer https://qiwi.com/',
					'Connection: keep-alive'
				)
			);
			$cont = curl_exec ($this -> curl);
			$jsonCont = json_decode ($cont);
			if (!isset ($jsonCont -> code -> value))
			{
				return 0;
			}
			return 1;
		}

		//1 today 2 yesterday 3 week
		public function history ($type)
		{
			curl_setopt ($this -> curl, CURLOPT_URL, 'https://qiwi.com/user/report/list.action');
			curl_setopt ($this -> curl, CURLOPT_POST, 1);
			curl_setopt ($this -> curl, CURLOPT_POSTFIELDS, 'type='.$type);
			curl_setopt ($this -> curl, CURLOPT_HTTPHEADER,
				array (
					'User-Agent Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0',
					'Accept:"text/html, */*; q=0.01"',
					'Accept-Language:"en-US,en;q=0.5"',
					'Accept-Encoding: deflate',
					'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With: XMLHttpRequest',
					'Referer: https://qiwi.com/report/list.action?type='.$type,
					'Connection: keep-alive'
				)
			);
			$cont = curl_exec ($this -> curl);
			if (preg_match_all ('|<div class="DateWithTransaction">.*<span class="date">(.*)</span>.*<span class="time">(.*)</span>.*<div class="transaction">(.*)</div>.*</div>|Usi', $cont, $dateWithTransaction) &&
			preg_match_all ('|<div class="IncomeWithExpend (.*)">.*<div class="cash">(.*)</div>|Usi', $cont, $incomeWithExpend) &&
			preg_match_all ('|<div class="ProvWithComment">.*<div class="provider">.*<span class="opNumber">(.*)</span>.*</div>.*<div class="comment">(.*)</div>|Usi', $cont, $provWithComment))
			{
				for ($i = 0; $i<count ($dateWithTransaction[3]); $i++)
				{
					$history [] = array (
						'date' => trim ($dateWithTransaction [1][$i]),
						'time' => trim ($dateWithTransaction [2][$i]),
						'transaction' => trim ($dateWithTransaction [3][$i]),
						'type' => trim ($incomeWithExpend [1][$i]),
						'cash' => preg_replace('|[^0-9]|', '', $incomeWithExpend [2][$i]),
						'number' => trim ($provWithComment [1][$i]),
						'comment' => trim ($provWithComment [2][$i])
					);
				}
				return $history;
			} else {
				return 0;
			}
		}
	}

	$qiwi = new qiwiPaymentClass;
	if ($qiwi -> auth ('+380983700000', '00000000') == 1)
	{
		echo 'authorized!';
		print_r ($qiwi -> history (3));
	} else {
		echo 'error!';
	}
?>
