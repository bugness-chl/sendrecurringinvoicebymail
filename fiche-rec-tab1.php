<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Chl <chl-dev@bugness.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Initially copied from modulebuilder/template/myobject_card.php

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

// Load needed classes/lib
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once 'class/sribmcustommailinfo.class.php';

// Get parameters
$id = GETPOST('id', 'int');
$output = '';

// Check security (take care of everything, even page layout, if something goes wrong)
restrictedArea($user, 'facture', $id, 'facture_rec');

// Load translation files required by the page
$langs->loadLangs(array('bills', 'compta', 'other', 'mails', 'products', 'companies', 'sendrecurringinvoicebymail@sendrecurringinvoicebymail'));

// Wrap everything in a do-while(false) as a try-catch mecanisme
// in order to print llxFooter whatever happens.
do {
    /**
     * Part 0 : Preparations
     */

    // Load necessary data
    $mailObject = new SRIBMCustomMailInfo($db);
    $form = new Form($db);
    if ($mailObject->fetch(null, $id, true) <= 0) {
        // Note : this should only happen when facture-rec doesn't exist or some database error.
        //        If sribmcustommailinfo doesn't exist in database, we should still get a instance of the template.
        setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
        break;
    }

    // List of senders (user, company, robot, ...)
    $listFrom = array();
    $listFrom['robot'] = $conf->global->MAIN_MAIL_EMAIL_FROM;
    $listFrom['company'] = $conf->global->MAIN_INFO_SOCIETE_NOM .' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
    if (!empty($user->email)) {
        $listFrom['user'] = $user->getFullName($langs) . ' <' . $user->email . '>';
    }
    // If the address in the SRIBM object is not the same as the user (basically,
    // the user has changed its email address or the address has been set by another
    // user), we add an 'old' = 'no modification' option.
    if ($mailObject->id
      && $mailObject->fromtype == 'user'
      && isset($listFrom['user'])
      && $listFrom['user'] != $mailObject->frommail
    ) {
        $listFrom['old'] = $mailObject->frommail;
    }

    // List of contacts of the third party
    $listContacts = $mailObject->fac_rec_object->thirdparty->thirdparty_and_contact_email_array(1);

    // Substitution array/string
    $helpforsubstitution = $langs->trans('AvailableVariables').' :<br>'."\n";
    $tmparray = getCommonSubstitutionArray($langs, 0, null, $mailObject->fac_rec_object);
    complete_substitutions_array($tmparray, $langs);
    foreach($tmparray as $key => $val) {
        $helpforsubstitution .= $key . ' -> ' . $langs->trans(dol_string_nohtmltag($val)) . '<br>';
    }


    /**
     * Part 1 : Treatment of form submission
     */

    // If the mail form has been submitted, check and record the data
    if (GETPOST('save')) {
        do {
            // Validate input data
            if (! array_key_exists(GETPOST('fromtype', 'alpha'), $listFrom)) {
                setEventMessages('Unexpected from value', null, 'errors');
                break;
            }
            if (GETPOST('sendto_socpeople', 'array') != array_intersect(GETPOST('sendto_socpeople', 'array'), array_keys($listContacts))) {
                setEventMessages("Unexpected contact value in 'to'", null, 'errors');
                break;
            }
            if (GETPOST('sendcc_socpeople', 'array') != array_intersect(GETPOST('sendcc_socpeople', 'array'), array_keys($listContacts))) {
                setEventMessages("Unexpected contact value in 'cc'", null, 'errors');
                break;
            }
            // Validate some non-breaking stuff after feeding
            if (empty(GETPOST('sendto_free', 'alpha')) && empty(GETPOST('sendto_socpeople', 'array'))) {
                // Kinda weird behaviour from CMailFile but better alert the user beforehand
                // FIXME: check if there is a workaround ?
                setEventMessages("In some configuration, CMailFile doesn't allow empty 'to' recipient. You should set at least one.", null, 'warnings');
                //break;
            }
            if (! strlen(GETPOST('subject', 'alpha'))) {
                // Kinda weird behaviour from CMailFile but better alert the user beforehand
                // FIXME: check if there is a workaround ?
                setEventMessages("In some configuration, CMailFile doesn't allow empty subject. You should set one.", null, 'warnings');
                //break;
            }
            if (! in_array(GETPOST('body_ishtml', 'int'), array('-1', '0', '1'), true)) {
                setEventMessages("Unexpected body_ishtml value", null, 'errors');
                break;
            }

            // Feed the input data to the model
            $mailObject->active = GETPOST('active', 'int') ? 1 : 0;
            $mailObject->addmaindocfile = GETPOST('addmaindocfile', 'int') ? 1 : 0;

            $mailObject->fromtype = GETPOST('fromtype', 'alpha');
            $mailObject->frommail = $listFrom[$mailObject->fromtype];

            $mailObject->sendto_free = GETPOST('sendto_free', 'alpha');
            $mailObject->sendto_thirdparty = in_array('thirdparty', GETPOST('sendto_socpeople', 'array'));

            $mailObject->sendcc_free = GETPOST('sendcc_free', 'alpha');
            $mailObject->sendcc_thirdparty = in_array('thirdparty', GETPOST('sendcc_socpeople', 'array'));

            $mailObject->subject = GETPOST('subject', 'alpha');
            $mailObject->body = GETPOST('body', 'restricthtml');
            $mailObject->body_ishtml = (int)GETPOST('body_ishtml', 'int');

            // Save into database
            if ($mailObject->id) {
                if ($mailObject->update($user) != 1) {
                    setEventMessages($langs->trans("ErrorSQL") . ' : ' . $mailObject->error, null, 'errors');
                    break;
                }
            } else {
                $mailObject->fk_facture_rec = $mailObject->fac_rec_object->id;
                if ($mailObject->create($user) < 0) {
                    setEventMessages($langs->trans("ErrorSQL") . ' : ' . $mailObject->error, null, 'errors');
                    break;
                }
            }
            // Update the linked contacts
            $data = array_merge(
                array_map(
                    function ($id) { return array('id' => $id, 'sendtype' => 'to'); },
                    array_filter(GETPOST('sendto_socpeople', 'array'), 'is_numeric')
                ),
                array_map(
                    function ($id) { return array('id' => $id, 'sendtype' => 'cc'); },
                    array_filter(GETPOST('sendcc_socpeople', 'array'), 'is_numeric')
                )
            );
            if ($mailObject->updateLinkSocPeople($data) != 1) {
                setEventMessages($langs->trans("ErrorSQL") . ' : ' . $mailObject->error, null, 'errors');
                break;
            }

            // Everything seems ok
            setEventMessages($langs->trans("CorrectlyUpdated"), null, 'mesgs');
            // ... + redirect to cleanly reload all data and avoid some F5 misbehaviour
            header("Location: fiche-rec-tab1.php?id=" . (int) $id, true, 302);
            return;
        } while(false);
    } else if (GETPOST('reset') && $mailObject->id) {
        if ($mailObject->delete() == 1) {
            // Success message...
            setEventMessages($langs->trans("ResetDone"), null, 'mesgs');

            // ... + redirect to cleanly reload all data
            header("Location: fiche-rec-tab1.php?id=" . (int) $id, true, 302);
            return;
        } else {
            setEventMessages($langs->trans("ErrorSQL") . " : " . $mailObject->error , null, 'errors');
        }
    }

    // Prepare the pre-selected id for To/Cc select inputs
    $preselected = array(
        'to' => array(),
        'cc' => array(),
    );
    if (GETPOSTISSET('sendto_socpeople') || GETPOSTISSET('sendcc_socpeople')) {
        // Retrieve data from last form submission
        $preselected['to'] = GETPOST('sendto_socpeople');
        $preselected['cc'] = GETPOST('sendcc_socpeople');
    } else {
        // Retrieve data from model
        foreach ($mailObject->linkToSocPeoples as $contact) {
            $preselected[$contact->pivot->sendtype][] = $contact->id;
        }
        // add the third-party's email in case sendXX_thirdparty is true
        if ($mailObject->sendto_thirdparty) {
            $preselected['to'][] = 'thirdparty';
        }
        if ($mailObject->sendcc_thirdparty) {
            $preselected['cc'][] = 'thirdparty';
        }
    }

    /**
     * Part 2 : Display
     */

    // Same tabs than the main page
    $head=invoice_rec_prepare_head($mailObject->fac_rec_object);
    $output .= dol_get_fiche_head($head, 'sendrecurringinvoicebymail', $langs->trans("RepeatableInvoice"), -1, 'bill');    // Add a div
    $output .= '<div class="inline-block floatleft valignmiddle refid refidpadding">' . $mailObject->fac_rec_object->ref . "</div>\n";
    $output .= '<div class="refidno">' . $langs->trans('ThirdParty') . ' : ' . $mailObject->fac_rec_object->thirdparty->getNomUrl(1) . "</div>\n";
    $output .= "</div><!-- Closing div class='tabBar' -->\n\n";

    $output .= '<div class="titre inline-block">' . $langs->trans("Options") . "</div>\n";
    $output .= '<form id="sribmform" name="sribmform" method="POST" action="#sribmform">';
    $output .= '<table class="liste" summary="mail options"><tbody>';
    $output .= '<tr class="oddeven">';
    $output .= '  <td><label for="active">' . $langs->trans('OptionEnable') . "</label></td>\n";
    $output .= '  <td><input type="checkbox" id="active" name="active" value="1"' . ((empty($_POST) && $mailObject->active || GETPOSTISSET('active')) ? ' checked="checked"' : '') . " /></td>\n";
    $output .= "</tr>\n";
    $output .= "</tbody></table>\n";

    // Little explanations
    $output .= '<div class="titre inline-block" style="margin-top: 1.5em;">' . $langs->trans("CustomizationTitle") . "</div>\n";
    // TODO: translation
    $output .= "<p>" . $langs->trans('CustomizationIntro', $langs->trans('Reset')) . "<br />\n";
    $output .= $langs->trans('CustomizationLinkToGlobalTemplate', DOL_URL_ROOT . '/admin/mails_templates.php?search_label=sendrecurring', DOL_URL_ROOT . '/admin/mails.php') . "</p>\n\n";

    $output .= '<table class="tableforemailform boxtablenotop" width="100%">';
    $output .= '<tr><td class="fieldrequired minwidth200">'.$langs->trans("MailFrom").'</td><td>';
    $defaultFrom = 'robot';
    if (isset($listFrom['old'])) {
        $defaultFrom = 'old';
    } elseif ($mailObject->id) {
        $defaultFrom = $mailObject->fromtype;
    }
    $output .= $form->selectarray('fromtype', $listFrom, GETPOST('fromtype', 'alpha') ? GETPOST('fromtype', 'alpha') : $defaultFrom);
    $output .= "</td></tr>\n";


    // Recipient(s)
    $output .= '<tr><td class="minwidth200 fieldrequired">';
    $output .= $form->textwithpicto($langs->trans("MailTo"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
    $output .= "</td>\n<td>";
    $output .= '<input class="minwidth200" id="sendto_free" name="sendto_free" value="'.(GETPOST("sendto_free", "alpha") ? GETPOST("sendto_free", "alpha") : htmlentities($mailObject->sendto_free)) . '" />';
    $output .= " " . $langs->trans("and") . "/" . $langs->trans("or") . " ";
    $output .= $form->multiselectarray('sendto_socpeople', $listContacts, $preselected['to']);
    $output .= "</td></tr>\n";


    // CC
    $output .= '<tr><td class="minwidth200">';
    $output .= $form->textwithpicto($langs->trans("MailCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
    $output .= "</td>\n<td>";
    $output .= '<input class="minwidth200" id="sendto_free" name="sendcc_free" value="'.(GETPOST("sendcc_free", "alpha") ? GETPOST("sendcc_free", "alpha") : htmlentities($mailObject->sendcc_free)) . '" />';
    $output .= " " . $langs->trans("and") . "/" . $langs->trans("or") . " ";
    $output .= $form->multiselectarray('sendcc_socpeople', $listContacts, $preselected['cc']);
    $output .= "</td></tr>\n";


    // Subject
    $output .= '<tr><td class="minwidth200 fieldrequired">';
    $output .= $form->textwithpicto($langs->trans("MailTopic"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfromtopic');
    $output .= "</td>\n<td>";
    $output .= '<input type="text" class="quatrevingtpercent" id="subject" name="subject" value="'. (GETPOST('subject', 'alpha') ? GETPOST('subject', 'alpha') : htmlentities($mailObject->subject)) . '" />';
    $output .= "</td></tr>\n";


    // addmaindocfile
    $output .= '<tr><td>' . $langs->trans('MailFile') . "</td>\n";
    $tmp_addmaindocfile = $mailObject->addmaindocfile;
    if (! empty($_POST)) {
        $tmp_addmaindocfile = GETPOSTISSET('addmaindocfile') ? 1 : 0;
    }
    $output .= '<td><input type="checkbox" name="addmaindocfile" value="1"' . ($tmp_addmaindocfile ? ' checked="checked"' : '') . ' /> ' . $langs->trans("JoinMainDoc") . "</td>\n";


    // body
    $output .= '<tr><td class="minwidth200" valign="top">';
    $output .= $form->textwithpicto($langs->trans("MailText"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
    $output .= "</td>\n<td>";


    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor = new DolEditor(
        'body',
        (GETPOST('body', 'alpha') ? GETPOST('body', 'alpha') : $mailObject->body),
        '',
        280,
        'dolibarr_mailings', // toolbar name
        'In', // toolbar location
        false, // toolbar start expanded
        true, // use local browser
        !empty($conf->global->FCKEDITOR_ENABLE_MAIL) // follow global conf about using ckeditor for mails.
    );
    $output .= $doleditor->Create(1);
    $output .= "</td></tr>\n";

    // body_ishtml
    $output .= '<tr><td>' . $langs->trans('MailBodyFormat') . "</td>\n";
    $tmp_ishtml = (int)(GETPOSTISSET('body_ishtml') ? GETPOST('body_ishtml', 'int') : $mailObject->body_ishtml);
    // selectarray() does funny things with -1 key, so we build it manually.
    //$output .= $form->selectarray('body_ishtml', $listBodyIsHtml, $mailObject->body_ishtml);
    $output .= '<td><select name="body_ishtml">';
    foreach (array(-1 => 'MailBodyFormatAutoDetect', 0 => 'MailBodyFormatPlainText', 1 => 'MailBodyFormatHtml') as $key => $item) {
        $output .= '<option value="' . $key . '"';
        $output .= ($key === $tmp_ishtml) ? ' selected="selected"' : '';
        $output .= '>' . $langs->trans($item) . "</option>\n";
    }
    $output .= "</select>\n</td>\n";

    $output .= "</table>\n";

    $output .= '<br><div class="center">';
    $output .= '<input class="button" type="submit" id="save" name="save" value="'.$langs->trans("Save").'" />';
    $output .= '<input class="button warning" type="submit" id="reset" name="reset" value="'.$langs->trans("Reset").'"';
    $output .= ($mailObject->id ? '' : ' title="' . $langs->trans('ResetTooltipNoCustomisationToReset') . '" disabled="disabled"') . ' />';
    $output .= "</div>\n";
} while (false);

// Print everything
llxHeader('', $langs->trans('RepeatableInvoice') . ' - ' . $langs->trans('SendingByMail'), '');
print $output;
llxFooter();
