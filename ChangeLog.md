# CHANGELOG SENDRECURRINGINVOICEBYMAIL FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)


## 0.3.2

Note: This release includes a DB schema modification. Reactivate the module to trigger it.

Enhancements:

* Mails can now be sent in HTML (via global module configuration and via the customzation tab).
* Mails can now be sent even when the invoice is a draft.


## 0.3.1

Small enhancements and fixes :

* links to the configuration of global elements (mail template, sender address)
* display attached files in the agenda's event
* fix the 0.2.7 "hacky way" import into the 0.3.0 way

## 0.3.0
Dedicated tab on the template invoice's page, to be able to customize more cleanly the recipients, and the email content.

The hacky way of 0.2.7 is converted at installation (reinstallation for upgrade) but is not used after that and the support will be dropped in a future release.

## 0.2.7
Add the possibility to overwrite some email fields (recipients, subject, body) for each template.

## 0.2.5
Little cleanup : no reload needed for last_main_doc.

## 0.2.3
Renaming from 'sendfacrecmail' to 'sendrecurringinvoicebymail'.

## 0.1.2
First working version.
