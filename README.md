# Job Tracker

A beginner PHP project for tracking job applications.

## Features

- Add a job with a company name and job title.
- Update the application status.
- Delete a job with a confirmation prompt.
- Search by company name or job title.
- Display success and validation messages.

## Technologies

- PHP
- HTML
- CSS
- JavaScript
- PHP Sessions

## Run locally

1. Install XAMPP.
- Start Apache and MySQL from the XAMPP Control Panel.
- Create a database named `job_tracker` using phpMyAdmin.
- Select the `job_tracker` database in phpMyAdmin.
- Open the Import tab and import `schema.sql` to create the jobs table.
- Copy `db.example.php` to `db.php`.
- Set your local database username and password in `db.php`.
2. Place the project folder in `C:\xampp\htdocs\job-tracker`.
3. Start Apache from the XAMPP Control Panel.
4. Open `http://localhost/job-tracker/` in your browser.



## Data storage

Jobs are stored in a MySQL-compatible database.
PHP sessions are used for CSRF protection and one-time success messages.


## Current limitations

- No user accounts yet.
- All visitors to the same installation share the same job list.
- This is a learning project and is not ready for public deployment.

## Learning context

Built step by step with AI-assisted guidance.
I implemented and manually tested adding, updating, deleting,
searching, and validating job entries.


## Technologies   

- MariaDB / MySQL
- PDO with prepared statements