Proyecto de ejemplo, con JWT, Registro de usuario, y API para modelo Activity


- Liga para postman <a href="https://martian-crater-637539.postman.co/workspace/My-Workspace~33a1c9db-c86b-4837-80b8-76c7e80fd71e/collection/8397075-fc0f0d9b-2729-45bb-81bc-ed25db9f3e24?action=share&creator=8397075">Aquí</a>


- composer create-project laravel/laravel example-app2
- crear la base de datos
- configurar el archivo .env

- php artisan migrate

- composer require laravel/ui
- php artisan ui bootstrap --auth
- npm install && npm run dev


para utilizar jwt
composer require tymon/jwt-auth --ignore-platform-reqs
php artisan jwt:secret (esto se configura en automatico en el archivo .env)


## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
