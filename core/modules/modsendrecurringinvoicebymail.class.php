<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Nicolas ZABOURI  <info@inovea-conseil.com>
 * Copyright (C) 2018 SuperAdmin
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
 *  \defgroup   sendrecurringinvoicebymail     Module sendrecurringinvoicebymail
 *  \brief      sendrecurringinvoicebymail module descriptor.
 *
 *  \file       htdocs/sendrecurringinvoicebymail/core/modules/modsendrecurringinvoicebymail.class.php
 *  \ingroup    sendrecurringinvoicebymail
 *  \brief      Description and activation file for module sendrecurringinvoicebymail
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module sendrecurringinvoicebymail
 */
class modsendrecurringinvoicebymail extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs,$conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 468101;     // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'sendrecurringinvoicebymail';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = "financial";
        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

        // Module label (no space allowed), used if translation string 'ModulesendrecurringinvoicebymailName' not found (sendrecurringinvoicebymail is name of module).
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModulesendrecurringinvoicebymailDesc' not found (sendrecurringinvoicebymail is name of module).
        $this->description = "Send generated invoice by email";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "This module hooks onto the recurring invoice generation to automatically send the generated PDF.";

        $this->editor_name = 'Bugness';
        $this->editor_url = 'https://code.bugness.org/Dolibarr/sendrecurringinvoicebymail';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '0.3.3';

        //Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';
        // Key used in llx_const table to save module status enabled/disabled (where SENDRECURRINGINVOICEBYMAIL is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'sendrecurringinvoicebymail@sendrecurringinvoicebymail';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            'triggers' => 0,                                    // Set this to 1 if module has its own trigger directory (core/triggers)
            'login' => 0,                                       // Set this to 1 if module has its own login method file (core/login)
            'substitutions' => 0,                               // Set this to 1 if module has its own substitution function file (core/substitutions)
            'menus' => 0,                                       // Set this to 1 if module has its own menus handler directory (core/menus)
            'theme' => 0,                                       // Set this to 1 if module has its own theme directory (theme)
            'tpl' => 0,                                         // Set this to 1 if module overwrite template dir (core/tpl)
            'barcode' => 0,                                     // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'models' => 0,                                      // Set this to 1 if module has its own models directory (core/modules/xxx)
            'css' => array(),
            //'css' => array('/sendrecurringinvoicebymail/css/sendrecurringinvoicebymail.css.php'), // Set this to relative path of css file if module has its own css file
            'js' => array(),
            //'js' => array('/sendrecurringinvoicebymail/js/sendrecurringinvoicebymail.js.php'),          // Set this to relative path of js file if module must load a js on all pages
            'hooks' => array('cron'),   // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
            'moduleforexternal' => 0                            // Set this to 1 if feature of module are opened to external users
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/sendrecurringinvoicebymail/temp","/sendrecurringinvoicebymail/subdir");
        $this->dirs = array("/sendrecurringinvoicebymail/temp");

        // Config pages. Put here list of php page, stored into sendrecurringinvoicebymail/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@sendrecurringinvoicebymail");

        // Dependencies
        $this->hidden = false;          // A condition to hide module
        $this->depends = array('modFacture');       // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->requiredby = array();    // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array();  // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("sendrecurringinvoicebymail@sendrecurringinvoicebymail");
        //$this->phpmin = array(5,4);                   // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(10,0);     // Minimum version of Dolibarr required by module
        $this->warnings_activation = array();           // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();       // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        //$this->automatic_activation = array('FR'=>'sendrecurringinvoicebymailWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;                             // If true, can't be disabled

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(0=>array('SENDRECURRINGINVOICEBYMAIL_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('SENDRECURRINGINVOICEBYMAIL_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
        // );
        $this->const = array(
            0 => array(
                'SENDRECURRINGINVOICEBYMAIL_BODY_ISHTML_DEFAULT',  // key
                'chaine',   // always 'chaine' ?
                '0',          // value
                'default format for mail body : -1 for auto-detect, 0 for plain text, 1 for HTML.',  // desc
                1,          // visible
                'current',  // current or allentities
                0,          // deleteonunactive
            ),
            //1=>array('SENDRECURRINGINVOICEBYMAIL_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
        );

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (! isset($conf->sendrecurringinvoicebymail) || ! isset($conf->sendrecurringinvoicebymail->enabled))
        {
            $conf->sendrecurringinvoicebymail=new stdClass();
            $conf->sendrecurringinvoicebymail->enabled=0;
        }


        // Array to add new pages in new tabs
        $this->tabs = array();
        $this->tabs[] = array('data'=>'invoice-rec:+sendrecurringinvoicebymail:SendingByMail:sendrecurringinvoicebymail@sendrecurringinvoicebymail:sendrecurringinvoicebymail/fiche-rec-tab1.php?id=__ID__');                   // To add a new tab identified by code tabname1
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@sendrecurringinvoicebymail:$user->rights->sendrecurringinvoicebymail->read:/sendrecurringinvoicebymail/mynewtab1.php?id=__ID__');                      // To add a new tab identified by code tabname1
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@sendrecurringinvoicebymail:$user->rights->othermodule->read:/sendrecurringinvoicebymail/mynewtab2.php?id=__ID__',     // To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                                                           // To remove an existing tab identified by code tabname
        //
        // Where objecttype can be
        // 'categories_x'     to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // 'contact'          to add a tab in contact view
        // 'contract'         to add a tab in contract view
        // 'group'            to add a tab in group view
        // 'intervention'     to add a tab in intervention view
        // 'invoice'          to add a tab in customer invoice view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'member'           to add a tab in fundation member view
        // 'opensurveypoll'   to add a tab in opensurvey poll view
        // 'order'            to add a tab in customer order view
        // 'order_supplier'   to add a tab in supplier order view
        // 'payment'          to add a tab in payment view
        // 'payment_supplier' to add a tab in supplier payment view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'project'          to add a tab in project view
        // 'stock'            to add a tab in stock view
        // 'thirdparty'       to add a tab in third party view
        // 'user'             to add a tab in user view


        // Dictionaries
        $this->dictionaries=array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@sendrecurringinvoicebymail',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),      // List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),                                                    // Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),   // Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),                                                                                   // Sort order
            'tabfield'=>array("code,label","code,label","code,label"),                                                                                  // List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),                                                                             // List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),                                                                            // List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),                                                                                                 // Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->sendrecurringinvoicebymail->enabled,$conf->sendrecurringinvoicebymail->enabled,$conf->sendrecurringinvoicebymail->enabled)                                              // Condition to show each dictionary
        );
        */


        // Boxes/Widgets
        // Add here list of php file(s) stored in sendrecurringinvoicebymail/core/boxes that contains class to show a widget.
        $this->boxes = array(
            //0=>array('file'=>'sendrecurringinvoicebymailwidget1.php@sendrecurringinvoicebymail','note'=>'Widget provided by sendrecurringinvoicebymail','enabledbydefaulton'=>'Home'),
            //1=>array('file'=>'sendrecurringinvoicebymailwidget2.php@sendrecurringinvoicebymail','note'=>'Widget provided by sendrecurringinvoicebymail'),
            //2=>array('file'=>'sendrecurringinvoicebymailwidget3.php@sendrecurringinvoicebymail','note'=>'Widget provided by sendrecurringinvoicebymail')
        );


        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = array(
            //0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/sendrecurringinvoicebymail/class/myobject.class.php', 'objectname'=>'MyObject', 'method'=>'doScheduledJob', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->sendrecurringinvoicebymail->enabled', 'priority'=>50)
        );
        // Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->sendrecurringinvoicebymail->enabled', 'priority'=>50),
        //                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->sendrecurringinvoicebymail->enabled', 'priority'=>50)
        // );


        // Permissions
        $this->rights = array();        // Permission array used by this module
        /*
        $r=0;
        $this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read myobject of sendrecurringinvoicebymail';   // Permission label
        $this->rights[$r][3] = 1;                   // Permission by default for new user (0/1)
        $this->rights[$r][4] = 'read';              // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)
        $this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)

        $r++;
        $this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update myobject of sendrecurringinvoicebymail';  // Permission label
        $this->rights[$r][3] = 1;                   // Permission by default for new user (0/1)
        $this->rights[$r][4] = 'write';             // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)
        $this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)

        $r++;
        $this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
        $this->rights[$r][1] = 'Delete myobject of sendrecurringinvoicebymail'; // Permission label
        $this->rights[$r][3] = 1;                   // Permission by default for new user (0/1)
        $this->rights[$r][4] = 'delete';                // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)
        $this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->sendrecurringinvoicebymail->level1->level2)
        */


        // Main menu entries
        $this->menu = array();          // List of menus to add
        $r=0;

        // Add here entries to declare new menus

        /* BEGIN MODULEBUILDER TOPMENU */
        /*
        $this->menu[$r++]=array('fk_menu'=>'',                          // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'top',                          // This is a Top menu entry
                                'titre'=>'sendrecurringinvoicebymail',
                                'mainmenu'=>'sendrecurringinvoicebymail',
                                'leftmenu'=>'',
                                'url'=>'/sendrecurringinvoicebymail/sendrecurringinvoicebymailindex.php',
                                'langs'=>'sendrecurringinvoicebymail@sendrecurringinvoicebymail',           // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1000+$r,
                                'enabled'=>'$conf->sendrecurringinvoicebymail->enabled',    // Define condition to show or hide menu entry. Use '$conf->sendrecurringinvoicebymail->enabled' if entry must be visible if module is enabled.
                                'perms'=>'1',                           // Use 'perms'=>'$user->rights->sendrecurringinvoicebymail->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);                             // 0=Menu for internal users, 1=external users, 2=both
        */

        /* END MODULEBUILDER TOPMENU */

        /* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
        $this->menu[$r++]=array(    'fk_menu'=>'fk_mainmenu=sendrecurringinvoicebymail',        // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',                         // This is a Left menu entry
                                'titre'=>'List MyObject',
                                'mainmenu'=>'sendrecurringinvoicebymail',
                                'leftmenu'=>'sendrecurringinvoicebymail_myobject_list',
                                'url'=>'/sendrecurringinvoicebymail/myobject_list.php',
                                'langs'=>'sendrecurringinvoicebymail@sendrecurringinvoicebymail',           // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1000+$r,
                                'enabled'=>'$conf->sendrecurringinvoicebymail->enabled',  // Define condition to show or hide menu entry. Use '$conf->sendrecurringinvoicebymail->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',                           // Use 'perms'=>'$user->rights->sendrecurringinvoicebymail->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);                             // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(    'fk_menu'=>'fk_mainmenu=sendrecurringinvoicebymail,fk_leftmenu=sendrecurringinvoicebymail',     // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',                         // This is a Left menu entry
                                'titre'=>'New MyObject',
                                'mainmenu'=>'sendrecurringinvoicebymail',
                                'leftmenu'=>'sendrecurringinvoicebymail_myobject_new',
                                'url'=>'/sendrecurringinvoicebymail/myobject_page.php?action=create',
                                'langs'=>'sendrecurringinvoicebymail@sendrecurringinvoicebymail',           // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1000+$r,
                                'enabled'=>'$conf->sendrecurringinvoicebymail->enabled',  // Define condition to show or hide menu entry. Use '$conf->sendrecurringinvoicebymail->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',                           // Use 'perms'=>'$user->rights->sendrecurringinvoicebymail->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);                             // 0=Menu for internal users, 1=external users, 2=both
        END MODULEBUILDER LEFTMENU MYOBJECT */


        // Exports
        $r=1;

        /* BEGIN MODULEBUILDER EXPORT MYOBJECT */
        /*
        $langs->load("sendrecurringinvoicebymail@sendrecurringinvoicebymail");
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='MyObjectLines';    // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]='myobject@sendrecurringinvoicebymail';
        $keyforclass = 'MyObject'; $keyforclassfile='/mymobule/class/myobject.class.php'; $keyforelement='myobject';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
        $r++; */
        /* END MODULEBUILDER EXPORT MYOBJECT */
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function init($options='')
    {
        // Launch SQL files
        $result=$this->_load_tables('/' . basename(dirname(dirname(dirname(__FILE__)))) . '/sql/');
        if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

        // Launch small and conditional SQL queries
        $sql = array();
        // we check if our model already exists
        $result = $this->db->query("SELECT COUNT(*) AS cpt FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE module = 'sendrecurringinvoicebymail'");
        if ($result) {
            $row = $this->db->fetch_object($result);
            if ($row->cpt == 0) {
                $sql[] = "INSERT INTO " . MAIN_DB_PREFIX."c_email_templates
                    (module, type_template, lang, label, joinfiles, topic, content)
                    VALUES (
                    'sendrecurringinvoicebymail',
                    'facture_send',
                    '',
                    'SendRecurringInvoiceByMail : original template',
                    '1',
                    '[__MYCOMPANY_NAME__] __(NewBill)__ __REF__',
                    '__(Hello)__,\n\nPlease find attached your new invoice.\n\nIn case of payment via bank transfer (our bank infos added at the bottom of the invoice), remember to add some references :\n- invoice number __REF__ for a one-time transfer,\n- or the contract/subscription reference __CONTRACT_REF__ for periodic transfers.\n\n__(Sincerely)__,\n\n__MYCOMPANY_NAME__')";
            }
        }

        // Reactivate the template in case the module has been
        // uninstalled which should have disabled the template.
        $sql[] = "UPDATE " . MAIN_DB_PREFIX . "c_email_templates SET enabled = 1 WHERE module = 'sendrecurringinvoicebymail'";

        // Cleaning up old (and ugly) system which
        // used note_private to store overriding data.
        // TODO : Remove this block at next version.
        $result = $this->db->query("SELECT r.rowid AS rid, r.note_private, s.rowid AS sid FROM " . MAIN_DB_PREFIX . "facture_rec AS r LEFT JOIN " . MAIN_DB_PREFIX . "sribm_custom_mail_info AS s ON r.rowid = s.fk_facture_rec WHERE r.note_private LIKE '%sendrecurringinvoicebymail::%'");
        if ($result) {
            while($row = $this->db->fetch_object($result)) {
                $mail_data = $this->parseCustomFieldsMail($row->note_private);
                $sid = $row->sid;
                if (! $sid) {
                    $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "sribm_custom_mail_info (fk_facture_rec, fromtype, frommail) VALUES (" . (int)$row->rid . ", 'robot', '" . $this->db->escape($conf->global->MAIN_MAIL_EMAIL_FROM) . "')");
                    $sid = $this->db->last_insert_id(MAIN_DB_PREFIX . 'sribm_custom_mail_info');
                }
                foreach (array('subject' => 'subject', 'body' => 'body', 'sendto' => 'sendto_free') as $key => $item) {
                    if (! empty($mail_data[$key])) {
                        // We loop on each field.
                        // Not optimized, I know.
                        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "sribm_custom_mail_info SET " . $item . " = '" . $this->db->escape($mail_data[$key]) . "' WHERE rowid = " . (int)$sid);
                        if ($key == 'sendto') {
                            // If the note_private specified a recipient, we disable sending to the
                            // main societe's mail.
                            $this->db->query("UPDATE " . MAIN_DB_PREFIX . "sribm_custom_mail_info SET sendto_thirdparty = 0 WHERE rowid = " . (int)$sid);
                        }
                    }
                }
                $regexps = array(
                    '/%%% sendrecurringinvoicebymail::subject.*%%%/sU',
                    '/%%% sendrecurringinvoicebymail::body.*%%%/sU',
                    '/%%% sendrecurringinvoicebymail::sendto.*%%%/sU',
                );
                $row->note_private = preg_replace($regexps, array('', '', ''), $row->note_private);
                $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture_rec SET note_private = '" . $this->db->escape($row->note_private) . "' WHERE rowid = " . (int)$row->rid);
            }
        }

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        // Disable the template
        // (instead of deleting it, which may cause unwanted work loss)
        // (Yeah, counterside is data bloat... sorry...)
        $sql[] = "UPDATE " . MAIN_DB_PREFIX . "c_email_templates SET enabled = 0 WHERE module = 'sendrecurringinvoicebymail'";

        return $this->_remove($sql, $options);
    }

    /**
     * FIXME: Obsolete. Replaced by SRIBMCustomMailInfo. To be removed.
     * For the time being, we abuse of note_private to store our customizations
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
