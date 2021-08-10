# SendRecurringInvoiceByMail for <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## Features

(en) This module sends by email the invoice generated with recurring invoices via scheduled jobs.

(fr) Ce module envoie par mail les factures générées automatiquement par les travaux planifiés et les factures modèles.

You can customize the mail globally or by recurring invoice.

![Screenshot n° 1](img/screenshot1.png?raw=true)

To edit the default global mail template, go to Home > Setup > Emails > Email templates, and modify the `SendRecurringInvoiceByMail : original template`. If you don't want to attach the PDF of the invoice to the mails, set the `Attach file` input to 0 (default: 1, PDF attached).

To edit the default sender address, go to Home > Setup > Emails, and edit the `Sender email for automatic emails` field.

This module is triggered by the cron (Scheduled jobs module) and will not send emails when manually generating an invoice.


## Requirements

It requires Dolibarr version 10.0 at least (first version with the `cron/afterCreationOfRecurringInvoice()` hook).

Don't forget to also activate the **Scheduled jobs** module.

Other modules are available on <a href="https://www.dolistore.com" target="_new">Dolistore.com</a>.


## Install

### From the ZIP file and GUI interface

Go to `Home` > `Setup` > `Modules/Applications` and finally the `Deploy/install external app/module` tab
and upload the module_sendrecurringinvoicebymail-x.y.z.zip file (you can get it from the
[original forge](https://code.bugness.org/Dolibarr/sendrecurringinvoicebymail/releases)
or [Github](https://github.com/bugness-chl/sendrecurringinvoicebymail/releases)).

Next, on the `Modules/Applications` page, activate the newly available sendrecurringinvoicebymail module,
and probably the Scheduled jobs (alias cron or modCron) integrated module too.


#### Troubleshooting

Note: If the module screen tells you there is no custom directory, check that your setup is correct: 

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
        
### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/sendrecurringinvoicebymail```

```sh
cd ....../custom
git clone git@github.com:bugness-chl/sendrecurringinvoicebymail.git sendrecurringinvoicebymail
```

Then, from your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module


## Updating instructions

* Disable the module,
* Update the files (see Install),
* Re-enable the module.


## Licenses

### Main code

![GPLv3 logo](img/gplv3.png)

GPLv3 or (at your option) any later version.

See file COPYING for more information.

### Documentation

All texts and readmes.

![GFDL logo](img/gfdl.png)
