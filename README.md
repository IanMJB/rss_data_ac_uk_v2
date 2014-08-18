# rss_data_ac_uk_v2
Revamped version of rss.data.ac.uk - updated back-end and now using Fat-Free PHP.

## Installation Instructions

### Database (MySQL) Initialisation
Fill in secrets.ini.template with relevant information to produce secrets.ini (leave in same directory as secrets.ini.template) - you can remove secrets.ini.template afterwards.

Ensure MySQL is set to use UTF-8 as default - in my.cnf, under `[mysqld]`, ensure the following are written:

`init_connect = 'SET collation_connection = utf8_unicode_ci'i`
`init_connect = 'SET NAMES utf8'`
`character-set-server = utf8`
`collation-server = utf8_unicode_ci`
`skip-character-set-client-handshake`

Run scripts/initialise_db.php.

### Populating Database
Fill in scripts/cron_script.sh.template with relevant path information to produce cron_script.sh (again, both leave in the same directory, and remove cron_script.sh.template if you wish).

Create the following directories: logs, logs/cron_logs and logs/db_counts.

Run scripts/cron_script.sh - this is advised over just running bin/main.php manually as this will cause the logs to be created in the correct location, and a database count to be run aftwards.

Add scripts/cron_script.sh to your crontab to run however often you like.

### Setting up Front-End
Depends on web server being used.

I am personally using Apache2:
* Create a .conf in /etc/apache2/sites-available with the DocumentRoot as <abs_path>/rss_data_v2.
* Run a2ensite on the .conf.
* Restart Apache2.

## Maintanence

### Logs
Logs will be stored in logs/cron_logs, and database counts in logs/db_counts.

Logs should show the progress of database population, and where it stopped if there was an issue.

Database counts show the size of each table after population and re-population, for comparison purposes.
