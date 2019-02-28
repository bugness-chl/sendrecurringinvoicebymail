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
 * \file    sendfacrecmail/class/actions_sendfacrecmail.class.php
 * \ingroup sendfacrecmail
 * \brief   Hook overload for SendFacRecEmail
 *
 * Put detailed description here.
 */

/**
 * Class Actionssendfacrecmail
 */
class Actionssendfacrecmail
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

		// On n'envoie la facture que si elle est validée
		if ($object->brouillon) {
			return 0;
		}
		// On n'envoie évidemment pas s'il n'y a pas d'adresse email renseignée
		if (empty($object->thirdparty->email)) {
			dol_syslog("Empty email for thirdparty " . $object->thirdparty->id . ". Not sending facturerec " . $facturerec->ref . " (id:" . $facturerec->id . ").");
			return 0;
		}

		// récupération du template du mail
		// (pas très précise mais je commence à en avoir marre de creuser tout dolibarr pour trouver les bonnes fonctions...)
		$result = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE module = 'sendfacrecmail' AND active = 1 AND enabled = '1' ORDER BY tms DESC LIMIT 1");
		if ( ! $result or ! ($template = $this->db->fetch_object($result))) {
			$this->error = "Can't find mail template for sendfacrecmail";
			$this->errors[] = $this->error;
			$error++;
			return -1;
		}

		// L'objet n'est pas à jour ('manque last_main_doc entre autres)
		$object->fetch($object->id);

		// Préparation des remplacements dans le sujet et le corps du mail
		$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
		complete_substitutions_array($substitutionarray, $langs, $object);  // lourd et n'a rien ajouté lors de mes tests

		// Par contre, il nous manque quelques trucs utiles...
		if ( ! empty($object->linkedObjects['contrat'])) {
			$contrat = reset($object->linkedObjects['contrat']); // on prend le premier qui vient.
			$substitutionarray['__CONTRACT_REF__'] = $contrat->ref;
		}

		// Initialisations and substitutions
		$sendto   = $object->thirdparty->name . ' <' . $object->thirdparty->email . '>';
		$from     = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$errorsTo = $conf->global->MAIN_MAIL_ERRORS_TO;
		$replyTo  = $conf->global->MAIN_MAIL_ERRORS_TO;
		$subject  = make_substitutions($template->topic,   $substitutionarray, $langs);
		$body     = make_substitutions($template->content, $substitutionarray, $langs);
		if (method_exists($object, 'makeSubstitution')) {
			$subject = $object->makeSubstitution($subject);
			$body    = $object->makeSubstitution($body);
		}

		// On regarde si on doit joindre le fichier
		$filePath = array();
		$fileMime = array();
		$fileName = array();
		if ($template->joinfiles) {
			$filePath = array(DOL_DATA_ROOT . '/' . $object->last_main_doc);
			$fileMime = array('application/pdf'); // FIXME: à rendre dynamique, même si ce sera toujours du PDF ?
			$fileName = array(basename($object->last_main_doc));
		}

		// envoi du mail
		$mailfile = new CMailFile(
			$subject,  // sujet
			$sendto,   // destinataire
			$from,     // expéditeur
			$body,     // corps du mail
			$filePath,
			$fileMime,
			$fileName,
			'', // CC
			'', // BCC
			0,  //deliveryreceipt
			0,  //msgishtml
			$errorsTo,
			'', // css
			'', // trackid
			'', // moreinheader
			'standard', // sendcontext
			$replyTo);

		if ($mailfile->sendfile()) {
			dol_syslog("Success sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").");

			// Adds info to object for trigger
			// (maybe make a copy of the object instead of modifying it directly ?)
			$object->email_msgid    = $mailfile->msgid;
			$object->email_from     = $from;
			$object->email_subject  = $subject;
			$object->email_to       = $sendto;
			//$object->email_tocc    = $sendtocc;
			//$object->email_tobcc   = $sendtobcc;
			$object->actiontypecode = 'AC_OTH_AUTO';
			$object->actionmsg2=$langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($from,4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($sendto,4,0,1);
			$object->actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
			$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
			$object->actionmsg = dol_concatdesc($object->actionmsg, $body);

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

}
