<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org> (2003-2009)
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Panel
 *
 */
define('AREA', 'admin');
require './lib/init.php';

use Froxlor\Database\Database;

if (isset($_POST['id'])) {
	$id = intval($_POST['id']);
} elseif (isset($_GET['id'])) {
	$id = intval($_GET['id']);
}

if ($page == 'message') {
	if ($action == '') {
		$log->logAction(\Froxlor\FroxlorLogger::ADM_ACTION, LOG_NOTICE, 'viewed panel_message');

		if (isset($_POST['send']) && $_POST['send'] == 'send') {
			if ($_POST['recipient'] == 0 && $userinfo['customers_see_all'] == '1') {
				$log->logAction(\Froxlor\FroxlorLogger::ADM_ACTION, LOG_NOTICE, 'sending messages to admins');
				$result = Database::query('SELECT `name`, `email`  FROM `' . TABLE_PANEL_ADMINS . "`");
			} elseif ($_POST['recipient'] == 1) {
				if ($userinfo['customers_see_all'] == '1') {
					$log->logAction(\Froxlor\FroxlorLogger::ADM_ACTION, LOG_NOTICE, 'sending messages to ALL customers');
					$result = Database::query('SELECT `firstname`, `name`, `company`, `email`  FROM `' . TABLE_PANEL_CUSTOMERS . "`");
				} else {
					$log->logAction(\Froxlor\FroxlorLogger::ADM_ACTION, LOG_NOTICE, 'sending messages to customers');
					$result = Database::prepare('
						SELECT `firstname`, `name`, `company`, `email`  FROM `' . TABLE_PANEL_CUSTOMERS . "`
						WHERE `adminid` = :adminid");
					Database::pexecute($result, array(
						'adminid' => $userinfo['adminid']
					));
				}
			} else {
				\Froxlor\UI\Response::standard_error('norecipientsgiven');
			}

			$subject = $_POST['subject'];
			$message = wordwrap($_POST['message'], 70);

			if (! empty($message)) {
				$mailcounter = 0;
				$mail->Body = $message;
				$mail->Subject = $subject;

				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

					$row['firstname'] = isset($row['firstname']) ? $row['firstname'] : '';
					$row['company'] = isset($row['company']) ? $row['company'] : '';
					$mail->AddAddress($row['email'], \Froxlor\User::getCorrectUserSalutation(array(
						'firstname' => $row['firstname'],
						'name' => $row['name'],
						'company' => $row['company']
					)));
					$mail->From = $userinfo['email'];
					$mail->FromName = (isset($userinfo['firstname']) ? $userinfo['firstname'] . ' ' : '') . $userinfo['name'];

					if (! $mail->Send()) {
						if ($mail->ErrorInfo != '') {
							$mailerr_msg = $mail->ErrorInfo;
						} else {
							$mailerr_msg = $row['email'];
						}

						$log->logAction(\Froxlor\FroxlorLogger::ADM_ACTION, LOG_ERR, 'Error sending mail: ' . $mailerr_msg);
						\Froxlor\UI\Response::standard_error('errorsendingmail', $row['email']);
					}

					$mailcounter ++;
					$mail->ClearAddresses();
				}

				\Froxlor\UI\Response::redirectTo($filename, array(
					'page' => $page,
					's' => $s,
					'action' => 'showsuccess',
					'sentitems' => $mailcounter
				));
			} else {
				\Froxlor\UI\Response::standard_error('nomessagetosend');
			}
		}
	}

	if ($action == 'showsuccess') {

		$success = 1;
		$sentitems = isset($_GET['sentitems']) ? (int) $_GET['sentitems'] : 0;

		if ($sentitems == 0) {
			$successmessage = $lng['message']['norecipients'];
		} else {
			$successmessage = str_replace('%s', $sentitems, $lng['message']['success']);
		}
	} else {
		$success = 0;
		$sentitems = 0;
		$successmessage = '';
	}

	$action = '';
	$recipients = '';

	if ($userinfo['customers_see_all'] == '1') {
		$recipients .= \Froxlor\UI\HTML::makeoption($lng['panel']['reseller'], 0);
	}

	$recipients .= \Froxlor\UI\HTML::makeoption($lng['panel']['customer'], 1);
	eval("echo \"" . \Froxlor\UI\Template::getTemplate('message/message') . "\";");
}
