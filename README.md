# Pre-requesites
This setup requires that a working [AMP](https://en.wikipedia.org/wiki/List_of_Apache–MySQL–PHP_packages) configuration on the machine intended to host the environment.

* PHP 7.3.8 
* [Composer](https://getcomposer.org)
* [wkhtmltopdf](https://wkhtmltopdf.org/)
* A Sendgrid account with an API key
* A fresh MySQL database.

# Project setup

We need to clone this repo into `var/www` of our local machine then it will create a new folder named `ilt-costa-rica-v2`. 

Project's main root directory will be `var/www/ilt-costa-rica-v2/`. we will run all commands from this directory.

Get dependencies:

    composer install

If composer command successfully runs then it's fine otherwise, we need to download `vendor` folder from live site and need to place that into our local directory i.e:`var/www/ilt-costa-rica-v2/`.


Following are the commands to create/extract archive of required files.

- Compress the required files

  `$ tar -cvf git.tar .git`
  `$ tar -cvf src.tar src`
  `$ tar -cvf vendor.tar vendor`

- Download the tar files locally and just uncompress them into the local directory

  `$ tar -xvf git.tar`
  `$ tar -xvf src.tar`
  `$ tar -xvf vendor.tar`


After this we need to clone database, clone assests, and update `.env` file. Following the steps to do so:
## Clone remote database
- Access [https://platform.cloudways.com/login].
- Ask product owner for credentials.
- Locate the `DEMO-PHP7.3` server.
- Access right hand side "www".
- Open the `ILT Costa Rica` project.
- Launch database manager.
- Export (top-left).
- Set output radio-buttons to save.
- Export.

        mysqldump -u demo_db_name -p demo_db_database_name > path_to_save_dump/dump.sql
        scp ssh_user@ssh_machine_address:path_to_save_dump/dump.sql /path/on/local/machine/dump.sql

- Import data into a new local database.

        mysql -u root -p local_db_name < /path/on/local/machine/dump.sql


## Clone remote assets
- Access [https://platform.cloudways.com/login].
- Ask product owner for credentials.
- Locate the `DEMO-PHP7.3` server.
- Access right hand side "www".
- Open the `ILT Costa Rica` project.
- From here you can see the information needed to SSH into the server.
- Access the server via SSH and copy `public/upload` and `public/uploads` into the equivalent paths of your local project.

        scp -r user@ipaddress:applications/qhpprqkzyu/public_html/public/uploads /path/on/local/public
        scp -r user@ipaddress:applications/qhpprqkzyu/public_html/public/upload /path/on/local/public


## Setup .env file
- Copy `.env.dist` into `.env`.
- Set `DATABASE_URL` to the url of your local database.
- Set `SENDGRID_USERNAME` and `SENDING_API_KEY` to your Sendgrid credentials.
- Set `WKHTMLTOPDF_PATH` to the path to your wkhtmltopdf binary mentioned in the pre-requesuites.

## Other vars
- `APP_ENV` can be set to `dev` or `prod`. `dev` provides more debug info while being slower.
- `USE_TAX` determines whether any tax values will be charged on the frontend.

# Commands
Takes asset files (stylesheets, images, JS scripts) and copies them to the `public` folder.

    php bin/console asset:install

Takes asset files (stylesheets, images, JS scripts) and symlinks them to the `public` folder.

    php bin/console assets:install --symlink

This command will start local symfony server, Needs to run this onto `root` Directory.

    php bin/console server:start
    
This command will stop local symfony server, Needs to run this onto `root` Directory.

    php bin/console server:stop

In `src/Wicrew/CoreBundle/Resources/public/stylesheets/scss`, takes the SCSS and compiles it into a minified CSS.

    sass style.scss:../style.min.css --style compressed


# Accounts
The backend can be accessed through http://SITE_URL/admin

Default admin is `admin` and the password is `admin12`.

# Other notes
- The root web folder is `public`.