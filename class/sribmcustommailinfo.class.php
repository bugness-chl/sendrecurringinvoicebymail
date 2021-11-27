<?php
/* Cpoyright (C) 2021 chl-dev@bugness.org
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

/**
 *  \file       class/sribmcustommailinfo.class.php
 *  \brief      File of class for objects storing custom mail informations (module SRIBM : SendRecurringInvoiceByMail)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * Class for objects storing custom mail informations.
 *
 * Part of module SRIBM : SendRecurringInvoiceByMail
 */
class SRIBMCustomMailInfo extends CommonObject
{
    /**
     * @var string Id to identify managed objects
     */
    public $element = 'sribmcustommailinfo';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'sribm_custom_mail_info';

    // Database fields

    /**
     * @var int
     */
    public $fk_facture_rec;

    /**
     * @var int
     */
    public $active = 1;

    /**
     * @var string
     */
    public $addmaindocfile = 1;

    /**
     * @var string  Type of sender, could be 'robot', 'user' or 'company',
     *              usually meaning the mail sender is determined by the
     *              PHP/Dolibarr config (cf. admin/mails.php?mainmenu=home ),
     *              this object's creator email or the company email.
     */
    public $fromtype = 'robot';

    /**
     * @var string
     */
    public $frommail;

    /**
     * @var int  1: Send to societe main's email, 0: don't.
     */
    public $sendto_thirdparty = 1;

    /**
     * @var string  Format expected: "Foo <foo@example.com>, bar@example.net"
     */
    public $sendto_free;

    /**
     * @var int  1: Send to societe main's email, 0: don't.
     */
    public $sendcc_thirdparty = 0;

    /**
     * @var string  Format expected: "Foo <foo@example.com>, bar@example.net"
     */
    public $sendcc_free;

    /**
     * @var int  1: Send to societe main's email, 0: don't.
     */
    public $sendbcc_thirdparty = 0;

    /**
     * @var string  Format expected: "Foo <foo@example.com>, bar@example.net"
     */
    public $sendbcc_free;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $body;

    /**
     * @var int   0: plain text,  1: html,  -1: auto (see CMailFile and dol_ishtml())
     */
    public $body_ishtml = 0;

    // End of database fields

    /**
     * FactureRec object linked to this object
     *
     * Note : the class Facture use $fac_rec and fk_fac_rec_source which both
     *        seem to be integer (I don't really see the distinction), so we suffix
     *        with '_object' to avoid future conflict.
     *
     * @var FactureRec
     */
    public $fac_rec_object;

    /**
     * @var array   SocPeoples linked via table llx_sribm_custom_mail_info_socpeople (FIXME: describe format)
     */
    public $linkToSocPeoples = array();

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf;

        $this->db = $db;

        // Fill default values
        $this->frommail = $conf->global->MAIN_MAIL_EMAIL_FROM;
    }

    /**
     * Create object into database
     *
     * Since only one mail can exist for each invoice template, this method
     * only insert a row with the id of the template and delegate all the work
     * on the rest of the data to the update() method.
     *
     * @param  User $user       User that creates (at the moment, we don't use it)
     * @param  int  $notrigger  1=do not execute triggers, 0 otherwise
     * @return int              >0 if OK, < 0 if KO
     */
    public function create(User $user, $notrigger = 0)
    {
        $error=0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " (fk_facture_rec, fromtype, frommail)";
        $sql .= sprintf(
            " VALUES (%d, '%s', '%s')",
            (int)$this->fk_facture_rec,
            $this->db->escape($this->fromtype),
            $this->db->escape($this->frommail)
        );

        dol_syslog("SRIBMCustomMailInfo::create", LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            $result  = $this->update($user, 1);
            if ($result < 0) {
                $this->db->rollback();
                return -3;
            }

            if (! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('SRIBM_CUSTOM_MAIL_INFO_CREATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if ($error) {
                dol_syslog(get_class($this) . "::create " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return $this->id;
    }

    /**
     * Update object in database
     *
     * @param  User $user       User that creates (at the moment, we don't use it)
     * @param  int  $notrigger  1=do not execute triggers, 0 otherwise
     * @return int              >0 if OK, < 0 if KO
     */
    public function update(User $user, $notrigger = 0)
    {
        $error=0;

        //$this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " SET ";
        $sql .= " fk_facture_rec = " . (int)$this->fk_facture_rec;
        $sql .= ", active = " . (int)$this->active;
        $sql .= ", addmaindocfile = " . (int)$this->addmaindocfile;
        $sql .= ", fromtype = '" . $this->db->escape($this->fromtype) . "'";
        $sql .= ", frommail = '" . $this->db->escape($this->frommail) . "'";
        $sql .= ", sendto_thirdparty  = " . (int)$this->db->escape($this->sendto_thirdparty);
        $sql .= ", sendto_free  = '" . $this->db->escape($this->sendto_free) . "'";
        $sql .= ", sendcc_thirdparty = " . (int)$this->db->escape($this->sendcc_thirdparty);
        $sql .= ", sendcc_free = '" . $this->db->escape($this->sendcc_free) . "'";
        $sql .= ", sendbcc_thirdparty = " . (int)$this->db->escape($this->sendbcc_thirdparty);
        $sql .= ", sendbcc_free = '" . $this->db->escape($this->sendbcc_free) . "'";
        $sql .= ", subject = '" . $this->db->escape($this->subject) . "'";
        $sql .= ", body = '" . $this->db->escape($this->body) . "'";
        $sql .= ", body_ishtml = " . (int)$this->body_ishtml;
        $sql .= " WHERE rowid = " . (int)$this->id;

        $result = $this->db->query($sql);
        if ($result) {
            if (! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('SRIBM_CUSTOM_MAIL_INFO_MODIFY', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if ($error) {
                dol_syslog(get_class($this) . "::update " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -$error;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        //$this->db->commit();
        return 1;
    }

    /**
     * Populate object with data from DB
     *
     * @param  int  $rowid  Id of the SRIBMCustomMailInfo to load
     * @param  int  $ref    Id of the fac_rec_object
     * @param  bool $fill_defaults_from_template  If true and the model doesn't exist in DB, fill attributes using the template
     * @return int         < 0 if KO, > 0 if OK
     */
    public function fetch($rowid, $ref=null, $fill_defaults_from_template = false)
    {
        global $conf;

        $sql = "SELECT rowid, fk_facture_rec, active, addmaindocfile, fromtype, frommail, sendto_thirdparty, sendto_free, sendcc_thirdparty, sendcc_free, sendbcc_thirdparty, sendbcc_free, subject, body, body_ishtml";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE " . (isset($ref) ? 'fk_facture_rec = ' . (int)$ref : "rowid = " . (int)$rowid);

        dol_syslog("SRIBMCustomMailInfo::fetch", LOG_DEBUG);

        $result = $this->db->query($sql);
        if (! $result) {
            $this->error=$this->db->lasterror();
            return -1;
        }

        if ($this->db->num_rows($result)) {
            $obj = $this->db->fetch_object($result);

            $this->id = $obj->rowid;
            $this->fk_facture_rec = $obj->fk_facture_rec;
            $this->active = $obj->active;
            $this->addmaindocfile = $obj->addmaindocfile;
            $this->fromtype = $obj->fromtype;
            $this->frommail = $obj->frommail;
            $this->sendto_thirdparty = $obj->sendto_thirdparty;
            $this->sendto_free = $obj->sendto_free;
            $this->sendcc_thirdparty = $obj->sendcc_thirdparty;
            $this->sendcc_free = $obj->sendcc_free;
            $this->sendbcc_thirdparty = $obj->sendbcc_thirdparty;
            $this->sendbcc_free = $obj->sendbcc_free;
            $this->subject = $obj->subject;
            $this->body = $obj->body;
            $this->body_ishtml = $obj->body_ishtml;
            $ref = $obj->fk_facture_rec;
        } elseif (!$fill_defaults_from_template) {
            $this->error = "SRIBMCustomMailInfo not found (id: " . var_export($rowid, true) . ", ref: " . var_export($ref, true);
            dol_syslog("SRIBMCustomMailInfo::fetch error " . $this->error, LOG_ERR);
        }

        // It seems to be usual to fully fetch by default (cf. Facture source
        // code with fetch_optionals()) so we copy the Dolibarr behaviour
        // Link to the facture_rec
        $this->fac_rec_object = new FactureRec($this->db);
        if ($this->fac_rec_object->fetch($ref) <= 0) {
            $this->error = "Unable to fetch FactureRec $ref.";
            dol_syslog("FactureRec::Fetch error " . $this->error, LOG_ERR);
            return -2;
        }
        if ($this->fac_rec_object->fetch_thirdparty() <= 0) {
            $this->error = "Unable to fetch ThirdParty for FactureRec $ref.";
            dol_syslog("FactureRec::FetchThirdParty error " . $this->error, LOG_ERR);
            return -3;
        }
        $result = $this->fac_rec_object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);  // This load $_facrec->linkedObjectsIds

        // link to socpeople
        if ($this->id) {
            // FIXME: create a dedicated PHP class?
            $sql = "SELECT fk_socpeople, sendtype";
            $sql .= " FROM " . MAIN_DB_PREFIX . "sribm_custom_mail_info_socpeople";
            $sql .= " WHERE fk_sribm_cmi = " . (int)$this->id;
            $result = $this->db->query($sql);
            if (! $result) {
                $this->error=$this->db->lasterror();
                return -1;
            }

            // reset the attribute
            $this->linkToSocPeoples = array();
            while ($obj = $this->db->fetch_object($result)) {
                $tmp = new Contact($this->db);
                if ($tmp->fetch($obj->fk_socpeople) > 0) {
                    $tmp->pivot = $obj;
                    $this->linkToSocPeoples[] = $tmp;
                }
            }
        }

        // Optionally, if the model hasn't been found (no id), we fill with the template's data
        if (!$this->id && $fill_defaults_from_template) {
            $result = $this->db->query("SELECT topic, content, joinfiles FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE module = 'sendrecurringinvoicebymail' AND active = 1 AND enabled = '1' ORDER BY tms DESC LIMIT 1");
            if ( ! $result or ! ($template = $this->db->fetch_object($result))) {
                $this->error = "Can't find mail template for sendrecurringinvoicebymail";
                dol_syslog("SRIBMCustomMailInfo::fetch error " . $this->error, LOG_ERR);
                return -4;
            }

            $this->subject = $template->topic;
            $this->body = $template->content;
            $this->addmaindocfile = $template->joinfiles;
        }

        return 1;
    }

    /**
     * Delete this object's record in database
     *
     * @return int <0 on error, >0 if OK
     */
    public function delete()
    {
        $error=0;

        //$this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE rowid = " . (int)$this->id;

        $result = $this->db->query($sql);
        if (! $result) {
            //$this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }

        // Call trigger
        $result = $this->call_trigger('SRIBM_CUSTOM_MAIL_INFO_DELETE', $user);
        if ($result < 0) {
            $error++;
        }
        // End call triggers

        if ($error) {
            dol_syslog(get_class($this) . "::delete " . $this->error, LOG_ERR);
            //$this->db->rollback();
            return -$error;
        }

        //$this->db->commit();
        return 1;
    }

    /**
     * Quick function to synchronize the links between this object and its contacts
     *
     * Format of the data :
     *  array(
     *    array('id' => id_contact_1, 'sendtype' => 'to'),
     *    array('id' => id_contact_2, 'sendtype' => 'cc'),
     *    ...
     *
     * @param  array $data  Format : see above
     * @return int <0 on error, >0 if OK
     */
    public function updateLinkSocPeople($data)
    {
        $this->db->begin();

        $result = $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "sribm_custom_mail_info_socpeople where fk_sribm_cmi = " . (int)$this->id);
        if (! $result) {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        if ($data) {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "sribm_custom_mail_info_socpeople";
            $sql .= " (fk_sribm_cmi, fk_socpeople, sendtype) VALUES (%d, %d, '%s')";
            foreach ($data as $item) {
                // We insert one by one to maximize compatibility
                // (if there happens to be thousands of links, I'll rework it :)
                $result = $this->db->query(sprintf(
                    $sql,
                    $this->id,
                    $item['id'],
                    $this->db->escape($item['sendtype'])
                ));
                if (! $result) {
                    $this->error=$this->db->lasterror();
                    $this->db->rollback();
                    return -2;
                }
            }
        }

        $this->db->commit();
        return 1;
    }

    /**
     * Helper to compile the recipients in one string from sendto_thirdparty,
     * sendto_free and socpeople.
     *
     * @param  string $sendtype        One of 'to', 'cc', 'bcc'
     * @param  bool   $filterBadEmail  If true, don't include recipients with empty/bad email address
     * @return string                  String compiling emails in the format 'Foo <foo@example.com>, bar@example.com'
     */
    public function compileEmails($sendtype, $filterBadEmails = false)
    {
        $output = array();

        $listContacts = $this->fac_rec_object->thirdparty->thirdparty_and_contact_email_array(1);

        switch ($sendtype) {
            case 'to':
                if ($this->sendto_free) {
                    $output[] = $this->sendto_free;
                }
                if ($this->sendto_thirdparty) {
                    $output[] = $listContacts['thirdparty'];
                }
                break;

            case 'cc':
                if ($this->sendcc_free) {
                    $output[] = $this->sendcc_free;
                }
                if ($this->sendcc_thirdparty) {
                    $output[] = $listContacts['thirdparty'];
                }
                break;

            case 'bcc':
                if ($this->sendbcc_free) {
                    $output[] = $this->sendbcc_free;
                }
                if ($this->sendbcc_thirdparty) {
                    $output[] = $listContacts['thirdparty'];
                }
                break;
        }

        foreach ($this->linkToSocPeoples as $contact) {
            if ($contact->pivot->sendtype == $sendtype) {
                $output[] = $listContacts[$contact->id];
            }
        }

        if ($filterBadEmails) {
            // TODO
        }

        return $output;
    }
}
