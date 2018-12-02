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
	public function generatedInvoice($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		$facturerec = $parameters['facturerec'];

		// On n'envoie la facture que si elle est validée
		if ( ! $object->brouillon) {
			if ($this->envoiMail($object, $facturerec)) {
				dol_syslog("Success sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").");
			} else {
				$this->errors[] = "Error sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").";
				dol_syslog("Error sending email for " . $facturerec->ref . " (id:" . $facturerec->id . ").");
				$error++;
			}
		}

		return ($error ? -1 : 0);
	}

	/**
	 * Fonction écrite un peu à l'arrache mais l'existant de Dolibarr
	 * ne semble pas très accessible.
	 *
	 * @return  boolean  True si envoi réussi
	 */
	function envoiMail($facture, $recurringFacture)
	{
		global $mysoc, $langs, $conf;

		// récupération du template du mail
		// (pas très précise mais je commence à en avoir marre de creuser tout dolibarr pour trouver les bonnes fonctions...)
		$result = $this->db->query("SELECT * from " . MAIN_DB_PREFIX . "c_email_templates WHERE module = 'sendfacrecmail' and active = 1 and enabled = '1' ORDER BY tms DESC LIMIT 1");
		if ( ! $result or ! ($template = $this->db->fetch_object($result))) {
			return false;
		}

		// L'objet n'est pas à jour ('manque last_main_doc entre autres)
		$facture->fetch($facture->id);

		// Préparation des remplacements dans le sujet et le corps du mail
		$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $facture);
		//complete_substitutions_array($substitutionarray, $langs, $facture);  // lourd et n'a rien ajouté lors de mes tests
		// Par contre, il nous manque quelques trucs utiles...
		if ( ! empty($facture->linkedObjects['contrat'])) {
			$contrat = reset($facture->linkedObjects['contrat']); // on prend le premier qui vient.
			$substitutionarray['__CONTRACT_REF__'] = $contrat->ref;
		}

		// Substitutions
		$subject = make_substitutions($template->topic,   $substitutionarray, $langs);
		$body    = make_substitutions($template->content, $substitutionarray, $langs);

		// On regarde si on doit joindre le fichier
		$filePath = array();
		$fileMime = array();
		$fileName = array();
		if ($template->joinfiles) {
			$filePath = array(DOL_DATA_ROOT . '/' . $facture->last_main_doc);
			$fileMime = array('application/pdf'); // FIXME: à rendre dynamique, même si ce sera toujours du PDF ?
			$fileName = array(basename($facture->last_main_doc));
		}

		// envoi du mail
		$mailfile = new CMailFile(
			$subject,         // sujet
			$facture->thirdparty->name . ' <' . $facture->thirdparty->email . '>', //destinataire
			$conf->global->MAIN_MAIL_EMAIL_FROM, // expéditeur (from)
			$body,            // corps du mail
			$filePath,
			$fileMime,
			$fileName,
			'', // CC
			'', // BCC
			0,  //deliveryreceipt
			0,  //msgishtml
			$conf->global->MAIN_MAIL_ERRORS_TO, //errors-to
			'', // css
			'', // trackid
			'', // moreinheader
			'standard', // sendcontext
			$conf->global->MAIN_MAIL_ERRORS_TO); //reply-to

		return $mailfile->sendfile();
	}
}
