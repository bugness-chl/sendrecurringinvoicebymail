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


-- We designed the schema too quickly : CMailFile doesn't let us manage
-- independently the text and html parts. All we can do is set the mode.
ALTER TABLE llx_sribm_custom_mail_info CHANGE COLUMN body_plaintext body mediumtext;
ALTER TABLE llx_sribm_custom_mail_info DROP COLUMN body_html;
ALTER TABLE llx_sribm_custom_mail_info ADD COLUMN body_ishtml smallint DEFAULT 0 NOT NULL;
