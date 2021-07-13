# TrapScan Backend
## Setup
TrapScan backend uses Laravel 8.

You can setup a dev environment using Docker with Laravel Sail.

##### 1. Install Docker
https://www.docker.com/products/docker-desktop
Start the program
##### 2. Clone Repo
```git clone git@github.com:KurtisPapple/TrapScan-Backend.git```

##### 3. Run Laravel Sail
https://laravel.com/docs/8.x/sail

``cd trapscan-backend``

``./vendor/bin/sail up``

##### 4. Run Migration and Seeders
``sail artisan migrate:fesh --seed``

##### 5. Running
http://localhost will be the main app
http://localhost:8090 has a phpmyadmin instance (credentials can be found in your .env)


## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.
