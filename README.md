# ToDoList

## Requirements

php 8^
symfony 6.3^
mysql 5.7^
database & database interface (like phpmyadmin, workbench, adminer ...)
Nodejs (& npm)

## Download the project

Terminal command :
git clone https://github.com/requenaGuillaume/todolist.git

Or go to : https://github.com/requenaGuillaume/todolist
and choose another way to download the project (like download zip or else)

## Run the local server

Use the "symfony serve" command in terminal (from the folder project)

## Install dependencies

Run the terminal command : "composer install"
Run the terminal command : "npm install"
Run the terminal command : "npm run build"

## Create database

Create database using terminal command :
symfony console doctrine:database:create

Run the migrations using terminal command :
symfony console doctrine:migrations:migrate

## Create the test database

Using custom command :
Create test database and run migrations
composer database:test:create

For futures migrations you should run :
APP_ENV=test symfony console doctrine:migrations:migrate

If, for any reason, you need to drop the database :
composer database:test:drop

Alternatively you can use :
APP_ENV=test symfony console doctrine:database:create
APP_ENV=test symfony console doctrine:migrations:migrate

APP_ENV=test symfony console doctrine:database:drop --force

## Fixtures

Run the fixtures using terminal command : "symfony console d:f:l"

Test account username and password :
username : coco
password : coco

## Run tests

Require a test database

vendor/bin/phpunit
or
npm run tests

With code coverage :
vendor/bin/phpunit --coverage-html public/test-coverage

## Cs fixer

Using custom command :
composer cs-fix
or
npm run csfixer

Using base command :
tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src

## Phpstan

Using custom command :
composer run-phpstan
or
npm run phpstan

Using base command :
vendor/bin/phpstan analyse src tests

## Quality check

A git hook pre-push is programmed.
It run the phpstan, cs fixer, and tests.
If one of them fails, nothing will be pushed and you will be forced to fix the issue.

And symfony insight launch analyze for every push, you can see the result directly on the pul request (check).

## Skip husky hook

If, for any reason, you need to skip the pre-push hook (with husky), which i do not recommand :
You can go to the .husky folder, pre-push file and comment everything in it
or
You can use the command git push origin <branch_name> --no-verify

## Webpack Encore

If the project doesn't work at this point, you may have to run :
npm run build

If you want to update css files, you will have to run :
npm run watch

## You're done

Project must be ready now
