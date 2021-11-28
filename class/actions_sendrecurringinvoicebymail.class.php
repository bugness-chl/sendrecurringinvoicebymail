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

 // Load needed classes/lib
 require_once 'sribmcustommailinfo.class.php';

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
     *  @param      DoliDB      $db      Database handler
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
        $mailObject = new SRIBMCustomMailInfo($this->db);
        if ($mailObject->fetch(null, $facturerec->id, true) != 1) {
            dol_syslog("Error loading SRIBMCustomMailInfo for facture rec " . (isset($facturerec->id) ? $facturerec->id : "(facturerec->id not set ??)"));
            return -1;
        }

        // Abort sending when inactive
        // (draft invoice or explictly disabled)
        if (!$mailObject->active) {
            return 0;
        }

        // Prepare the substitions for mail's subject and message (ex-body)
        $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
        complete_substitutions_array($substitutionarray, $langs, $object);  // lourd et n'a rien ajouté lors de mes tests

        // Adding some useful substitions of our own...
        if ( ! empty($object->linkedObjects['contrat'])) {
            $contrat = reset($object->linkedObjects['contrat']); // no deep search, we take the first linked contract
            $substitutionarray['__CONTRACT_REF__'] = $contrat->ref;
        }

        // Initialisations
        $mail_data = array(
            'from'     => $mailObject->frommail,
            'to'       => implode(', ', $mailObject->compileEmails('to', true)),
            'cc'       => implode(', ', $mailObject->compileEmails('cc', true)),
            'bcc'      => implode(', ', $mailObject->compileEmails('bcc', true)),
            'errorsTo' => $conf->global->MAIN_MAIL_ERRORS_TO,
            'replyTo'  => $conf->global->MAIN_MAIL_ERRORS_TO,
            'subject'  => $mailObject->subject,
            'message'  => $mailObject->body,
            'ishtml'   => $mailObject->body_ishtml,
        );

        // Check that we have a recipient, to avoid some frequent error...
        if (empty($mail_data['to'] . $mail_data['cc'] . $mail_data['bcc'])) {
            dol_syslog("Empty recipient for thirdparty " . $object->thirdparty->id . ". Not sending facturerec " . $facturerec->ref . " (id:" . $facturerec->id . ").");
            return 0;
        }

        // Make the substitutions
        foreach (array('subject', 'message') as $key) {
            $mail_data[$key] = make_substitutions($mail_data[$key], $substitutionarray, $langs);
            if (method_exists($object, 'makeSubstitution')) {
                $mail_data[$key] = $object->makeSubstitution($mail_data[$key]);
            }
        }

        // Check if we have to attach the file
        $filePath = array();
        $fileMime = array();
        $fileName = array();
        if ($mailObject->addmaindocfile) {
            $filePath = array(DOL_DATA_ROOT . '/' . $object->last_main_doc);
            $fileMime = array('application/pdf'); // FIXME: à rendre dynamique, même si ce sera toujours du PDF ?
            $fileName = array(basename($object->last_main_doc));
        }

        // At last, send the mail
        $mailfile = new CMailFile(
            $mail_data['subject'],
            $mail_data['to'],
            $mail_data['from'],
            $mail_data['message'],
            $filePath,
            $fileMime,
            $fileName,
            $mail_data['cc'], // CC
            $mail_data['bcc'], // BCC
            0,  //deliveryreceipt
            $mail_data['ishtml'],  //msgishtml
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
            $object->email_to       = $mail_data['to'];
            $object->email_tocc     = $mail_data['cc'];
            $object->email_tobcc    = $mail_data['bcc'];
            $object->actiontypecode = 'AC_OTH_AUTO';
            $object->actionmsg2=$langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($mail_data['from'],4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($mail_data['to'],4,0,1);
            $object->actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($mail_data['from']);
            $object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($mail_data['to']));
            $object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('MailTopic') . ": " . $mail_data['subject']);
            $object->actionmsg = dol_concatdesc($object->actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
            $object->actionmsg = dol_concatdesc($object->actionmsg, $mail_data['message']);
            $object->actionmsg = dol_concatdesc($object->actionmsg, "\n----- Attached file(s) -----");
            $object->actionmsg = dol_concatdesc($object->actionmsg, implode(', ', $fileName));

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
