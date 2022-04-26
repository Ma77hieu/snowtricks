# Snowtricks installation process

### 1.Clone the repository
run `git clone https://github.com/Ma77hieu/snowtricks.git`

### 2.Install required libraries
run `composer install`

### 3.Configure your database inside the .env file 
place the correct values in the DATABASE_URL constant of the .env file

### 4.Configure the mailer inside the .env file 
place the correct value in MAILER_DSN constant of the .env file

### 5.Create your database 
run `php bin/console doctrine:database:create`

### 6.Apply the project migrations 
in order to create the database tables, run
`php bin/console doctrine:migrations:migrate`

### 7.Populate database with examples 
following command applies for mysql: while being in the root directory run `mysql -u username -p database_name < exampleResources/databaseSQLfiles/expleData.sql`

### 8.Add the images used by the database
while being in the root directory run `mv exampleResources/TestDbImages/* public/uploads/images/`