<?php
/* Copyright (C) 2018 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    sendrecurringinvoicebymail/class/actions_sendrecurringinvoicebymail.class.php
 * \ingroup sendrecurringinvoicebymail
 * \brief   Hook overload for cron / afterCreationOfRecurringInvoice()
 *
 * Put detailed description here.
 */

/**
 * Class Actionssendrecurringinvoicebymail
 */
class Actionssendrecurringinvoicebymail
{
	/**
	* @var DoliDB Database handler.
	*/
	public $db;

	/**
	* @var string Error code (or message)
	*/
	public $error = '';

	/**
	* @var array Errors
	*/
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function afterCreationOfRecurringInvoice($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		$langs->load('mails');

		$error = 0; // Error counter

		$facturerec = $parameters['facturerec'];

		// We only send the mail when the invoice is not a draft
		if ($object->brouillon) {
			return 0;
		}

		if (empty($object->linkedObjects['contrat'])) {
			return 0;
		}

		$contract = reset($object->linkedObjects['contrat']);

		$contacts = $contract->liste_contact(-1, 'external', 0, 'BILLING');
		if (empty($contacts)) {
			dol_syslog("No billing contact for contract " . $contract->id . ". Not sending facturerec " . $facturerec->ref . " (id:" . $facturerec->id . ").");
			return 0;
		}

		// Fetch the mail template
		// (pas très précise mais je commence à en avoir marre de creuser tout dolibarr pour trouver les bonnes fonctions...)
		$result = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE module = 'sendrecurringinvoicebymail' AND active = 1 AND enabled = '1' ORDER BY tms DESC LIMIT 1");
		if ( ! $result or ! ($template = $this->db->fetch_object($result))) {
			$this->error = "Can't find mail template for sendrecurringinvoicebymail";
			$this->errors[] = $this->error;
			$error++;
			return -1;
		}

		// Prepare the substitions for mail's subject and body
		$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
		complete_substitutions_array($substitutionarray, $langs, $object);  // lourd et n'a rien ajouté lors de mes tests

		// Adding some useful substitions of our own...
		$substitutionarray['__CONTRACT_REF__'] = $contract->ref;

		$sendto = '';
		foreach($contacts as $contact) {
			if (empty($contact['email'])) {
				continue;
			}

			$sendto .= $contact['firstname'] . ' ' . $contact['lastname'] . ' <' . $contact['email'] . '>,';
		}

		if (empty($sendto)) {
			dol_syslog("No billing contact with an email for contract " . $contract->id . ". Not sending facturerec " . $facturerec->ref . " (id:" . $facturerec->id . ").");
			return 0;
		}

		// Initialisations
		$mail_data = array(
			'sendto'   => $sendto,
			'from'     => $conf->global->MAIN_MAIL_EMAIL_FROM,
			'errorsTo' => $conf->global->MAIN_MAIL_ERRORS_TO,
			'replyTo'  => $conf->global->MAIN_MAIL_ERRORS_TO,
			'subject'  => $template->topic,
			'body'     => $template->content,
		);

		// If the invoice has some custom parameters (subject, body, sendto, ...)
		$mail_data = array_merge($mail_data, $this->getCustomFieldsMail($object));

		// Make the substitutions
		foreach (array('subject', 'body') as $key) {
			$mail_data[$key] = make_substitutions($mail_data[$key], $substitutionarray, $langs);
			if (method_exists($object, 'makeSubstitution')) {
				$mail_data[$key] = $object->makeSubstitution($mail_data[$key]);
			}
		}

		// Check if we have to attach the file
		$filePath = array();
		$fileMime = array();
		$fileName = array();
		if ($template->joinfiles) {
			$filePath = array(DOL_DATA_ROOT . '/' . $object->last_main_doc);
			$fileMime = array('application/pdf'); // FIXME: à rendre dynamique, même si ce sera toujours du PDF ?
			$fileName = array(basename($object->last_main_doc));
		}

		// At last, send the mail
		$mailfile = new CMailFile(
			$mail_data['subject'],
			$mail_data['sendto'],
			$mail_data['from'],
			$mail_data['body'],
			$filePath,
			$fileMime,
			$fileName,
			'', // CC
			'', // BCC
			0,  //deliveryreceipt
			0,  //msgishtml
			$mail_data['errorsTo'],
			'', // css
			'', // trackid
			'', // moreinheader
			'standard', // sendcontext
			$mail_data['replyTo']);

		if ($mailfile->sendfile()) {
			dol_syslog("Success sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").");

			// Adds info to object for trigger
			// (maybe make a copy of the object instead of modifying it directly ?)
			$object->email_msgid    = $mailfile->msgid;
			$object->email_from     = $mail_data['from'];
			$object->email_subject  = $mail_data['subject'];
			$object->email_to       = $mail_data['sendto'];
			//$object->email_tocc    = $sendtocc;
			//$object->email_tobcc   = $sendtobcc;
			$object->actiontypecode = 'AC_OTH_AUTO';
			$object->actionmsg2=$langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($mail_data['from'],4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($mail_data['sendto'],4,0,1);
			$object->actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($mail_data['from']);
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($mail_data['sendto']));
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTopic') . ": " . $mail_data['subject']);
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
			$object->actionmsg = dol_concatdesc($object->actionmsg, $mail_data['body']);

			// Launch triggers
			$interface = new Interfaces($this->db);
			$resultTrigger = $interface->run_triggers('BILL_SENTBYMAIL', $object, $user, $langs, $conf);
		} else {
			$this->error    = "Error sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").";
			$this->errors[] = $this->error;
			dol_syslog($this->error);
			$error++;
		}

		return ($error ? -1 : 0);
	}


	/**
	 * FIXME: For the time being, we abuse of note_private to store our customizations
	 */
	public function getCustomFieldsMail($object)
	{
		return $this->parseCustomFieldsMail($object->note_private);
	}

	/**
	 * FIXME: For the time being, we abuse of note_private to store our customizations
	 *
	 * This expect something like this in note_private:
	 *  This is a good client... (other private infos)
	 *  %%% sendrecurringinvoicebymail::subject
	 *  New invoice __REF__
	 *  %%%
	 *  %%% sendrecurringinvoicebymail::sendto
	 *  recipient1@example.org, recipient2@example.org
	 *  %%%
	 *  %%% sendrecurringinvoicebymail::body
	 *  Hello dear client,
	 *  Please find attached...
	 *  %%%
	 */
	public function parseCustomFieldsMail($data)
	{
		$output = [];

		// Remove eventual windows' "\r"
		$data = str_replace("\r", "", $data);

		$regexps = array(
			'subject' => '/(^|\n)%%% sendrecurringinvoicebymail::subject\n(?<subject>.*)%%%(\n|$)/sU',
			'body'    => '/(^|\n)%%% sendrecurringinvoicebymail::body\n(?<body>.*)%%%(\n|$)/sU',
			'sendto'  => '/(^|\n)%%% sendrecurringinvoicebymail::sendto\n(?<sendto>.*)%%%(\n|$)/sU',
		);
		foreach ($regexps as $key => $r) {
			$result_regexp = [];
			if (preg_match_all($r, $data, $result_regexp)) {
				$output[$key] = trim($result_regexp[$key][0]);
			}
		}

		return $output;
	}

}
