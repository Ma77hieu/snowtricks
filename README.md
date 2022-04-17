
#1- Install the app
##1-1 Clone the repository
Source code available here: https://github.com/Ma77hieu/snowtricks

##1-2 Requirements (from Symfony official documentation)
extract from https://symfony.com/doc/current/setup.html:
Before creating your first Symfony application you must:
Install PHP 8.0.2 or higher and these PHP extensions (which are installed and enabled by default in most PHP 8 installations): Ctype, iconv, PCRE, Session, SimpleXML, and Tokenizer;
Install Composer, which is used to install PHP packages.
Optionally, you can also install Symfony CLI. This creates a binary called symfony that provides all the tools you need to develop and run your Symfony application locally.

##1-3 Install dependencies:
In your terminal, run:
>composer install
 
##1-4 Create your own local environnement variables
rename the example.env.local file in .env.local

###1-4-1 Genereate your APP_SECRET_KEY
Inside a php terminal run
> php generateAppSecretKey.php

Copy the result and paste it it as APP_SECRET_KEY value in your .env and .env.local files

###1-4-2 Ensure email delivery for user signup verification and reset password
The value of the MAILER_DSN environment variable differs based on your email provider.
Please refer to https://symfony.com/doc/current/mailer.html#transport-setup and make sure you have all necessary setup done on your email account's parameters.

###1-4-3 Connect your database
Set the value of your DATABASE_URL environment variable based on your DB type and your credentials. More details here: https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files

##1-5 Create database:
in your terminal, run:
> doctrine:migrations:migrate

***
#2- Add test data to the application
The exampleResources directory contains the necessary database data and uploaded pictures in order for you to try the app.

###2-1 Populate database with examples:
Run all files inside the exampleResources/databaseSQLfiles directory

### 2-2 Insert examples of uploaded images:
Copy all the images located in root/exampleResources/TestDbImages and paste them in root/public/uploads/images
