<?php

	if (!file_exists(__DIR__ . '/config.php')) {
		echo 'Please set up config.php', "\n";
		die();
	}

	require_once(__DIR__ . '/config.php');

	foreach ($officers as $officer) {
		echo 'Checking ', $officer['name'], '...', "\n";

		$url = 'https://api.companieshouse.gov.uk/officers/' . $officer['officer_id'] . '/appointments';
		$curlRes = curl_init();
		curl_setopt($curlRes, CURLOPT_URL, $url);
		curl_setopt($curlRes, CURLOPT_USERPWD, API_KEY . ':');
		curl_setopt($curlRes, CURLOPT_HEADER, false);
		curl_setopt($curlRes, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curlRes);
		curl_close($curlRes);

		$result = json_decode($result, TRUE);

		$new = [];
		foreach ($result['items'] as $company) {
			$director = $company['appointed_to'];

			//TODO: put this in sql lite or something that isnt files on disk
			$path = CACHE . $director['company_number'] . '_' . $officer['officer_id'] . '.txt';
			if (!file_exists($path)) {
				$new[] = $director['company_name'];
				file_put_contents($path, NULL);
			}
		}

		if (count($new) > 0) {
			$message = $officer['name'] . ' has new appointments on companies house. View more details at https://beta.companieshouse.gov.uk/officers/' . $officer['officer_id'] . '/appointments' . "\n\n";
			$notify = implode(',', $officer['notify']);
			foreach ($new as $appointment) {
				echo 'NEW APPOINTMENT - ', $appointment, "\n";
				$message .= $appointment . "\n";
			}
			$subject = '[Officer Watch] ' . $officer['name'];
			$to = MAIL_TO;
			$headers = implode("\r\n", ['From: ' . MAIL_FROM, 'BCC: ' . $notify]);
			mail($to, $subject, $message, $headers);
		} else {
			echo 'Nothing new', "\n";
		}
	}

