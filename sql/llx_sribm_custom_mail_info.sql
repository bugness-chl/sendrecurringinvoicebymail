-- Copyright (C) 2021 Chl <chl-dev@bugness.org>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


-- Sorry for the schema's complexity, but we have to manage 3 types
-- of recipients :
-- * the email in the societe/company's profile (default for the module)
-- * the contacts (table 'llx_socpeople')
-- * the email from the free text input
-- The From can also be a little difficult since, for the time being,
-- we avoid a free text input for the source email.
CREATE TABLE llx_sribm_custom_mail_info(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	fk_facture_rec INTEGER NOT NULL,
	active smallint DEFAULT 1 NOT NULL,
	addmaindocfile smallint DEFAULT 1 NOT NULL,
	fromtype text NOT NULL,
	frommail text NOT NULL,
	sendto_thirdparty smallint DEFAULT 1 NOT NULL,
	sendto_free text,
	sendcc_thirdparty smallint DEFAULT 0 NOT NULL,
	sendcc_free text,
	sendbcc_thirdparty smallint DEFAULT 0 NOT NULL,
	sendbcc_free text,
	subject text,
	body_plaintext mediumtext,
	body_html mediumtext
) ENGINE=innodb;


-- sendtype should only be one of 'to', 'cc' and 'bcc' but
-- I'm kinda afraid to do an ENUM with Dolibarr black magic on SQL.
CREATE TABLE llx_sribm_custom_mail_info_socpeople(
	fk_sribm_cmi INTEGER NOT NULL,
	fk_socpeople INTEGER NOT NULL,
	sendtype text NOT NULL
) ENGINE=innodb;
