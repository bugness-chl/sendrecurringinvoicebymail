# SendRecurringInvoiceByMail for <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## Features

This module send the PDF generated with recurring invoices by email to the client.

You can customize the mail template in Home > Setup > Emails > Email templates.

Beta - test in progress : you can also customize for each template invoice, by adding some of those blocks in the private notes of the template.
```
This is a good client (this is outside of the %%% blocks so it won't appear in the mails :)

%%% sendrecurringinvoicebymail::body
Hello dear client,

Please find attached... invoice __REF__...

__(Sincerely)__,

__MYCOMPANY_NAME__
%%%

%%% sendrecurringinvoicebymail::subject
My custom subject
%%%
%%% sendrecurringinvoicebymail::sendto
test1@example.org, "Mr. Test2" <test2@example.com>
%%%
```


## Requirements

It requires Dolibarr version 10.0 at least (first version with the 'cron/afterCreationOfRecurringInvoice()' hook).

<!--
![Screenshot sendrecurringinvoicebymail](img/screenshot_sendrecurringinvoicebymail.png?raw=true "sendrecurringinvoicebymail"){imgmd}
-->

Other modules are available on <a href="https://www.dolistore.com" target="_new">Dolistore.com</a>.



<!--
### Translations

Translations can be define manually by editing files into directories *langs*. 

This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service. 

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


<!--

Install
-------

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.


Note: If this screen tell you there is no custom directory, check your setup is correct: 

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

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module



-->


Licenses
--------

### Main code

![GPLv3 logo](img/gplv3.png)

GPLv3 or (at your option) any later version.

See file COPYING for more information.

#### Documentation

All texts and readmes.

![GFDL logo](img/gfdl.png)
