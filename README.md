# symfonyTest

1. git clone https://github.com/aykutyilmaz/symfonyTest.git
2. docker-compose build
3. docker-compose up -d
4. docker-compose exec php-fpm bash
5. composer install
6. php bin/console doctrine:schema:create
7. php bin/console doctrine:database:import dump.sql

Postman Collections:
https://github.com/aykutyilmaz/symfonyTest/blob/master/symfonyTest.postman_collection.json

Postman Environment:
https://github.com/aykutyilmaz/symfonyTest/blob/master/symfonyTest.postman_environment.json
