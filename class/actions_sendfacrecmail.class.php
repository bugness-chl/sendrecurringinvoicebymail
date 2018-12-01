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
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		$fp = fopen('/tmp/vardump.txt', 'w');
		fwrite($fp, serialize($parameters, $object, $action, $hookmanager));
		fclose($fp);
		return 0;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		{
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (! $error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
	public function writeSQL($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		//$object = "SELECT rowid FROM llx_facture_rec WHERE false";

		$fp = fopen('/tmp/writesql-vardump.txt', 'w');
		fwrite($fp, serialize($parameters));
		fwrite($fp, serialize($object));
		fwrite($fp, serialize($action));
		fwrite($fp, serialize($hookmanager));
		fclose($fp);
		return 0;
	}
	public function generatedInvoice($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		$fp = fopen('/tmp/generatedinvoice-vardump.txt', 'w');
		fwrite($fp, serialize($parameters));
		fwrite($fp, serialize($object));
		fwrite($fp, serialize($action));
		fwrite($fp, serialize($hookmanager));
		fclose($fp);
		return 0;
	}
}
