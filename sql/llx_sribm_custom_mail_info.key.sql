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


-- BEGIN MODULEBUILDER INDEXES
--ALTER TABLE llx_mymodule_myobject ADD INDEX idx_fieldobject (fieldobject);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_mymodule_myobject ADD UNIQUE INDEX uk_mymodule_myobject_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_mymodule_myobject ADD CONSTRAINT llx_mymodule_myobject_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mymodule_myotherobject(rowid);

ALTER TABLE llx_sribm_custom_mail_info ADD UNIQUE INDEX llx_sribm_custom_mail_info_uniq_frec (fk_facture_rec);
ALTER TABLE llx_sribm_custom_mail_info ADD CONSTRAINT llx_sribm_custom_mail_info_fk_rowid FOREIGN KEY (fk_facture_rec) REFERENCES llx_facture_rec(rowid) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE llx_sribm_custom_mail_info_socpeople ADD CONSTRAINT llx_sribm_custom_mail_info_socpeople_c_fk FOREIGN KEY (fk_sribm_cmi) REFERENCES llx_sribm_custom_mail_info(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE llx_sribm_custom_mail_info_socpeople ADD CONSTRAINT llx_sribm_custom_mail_info_socpeople_s_fk FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
