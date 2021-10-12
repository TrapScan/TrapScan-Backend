# TrapScan Backend
## Setup
TrapScan backend uses Laravel 8.

You can setup a dev environment using Docker with Laravel Sail.

##### 1. Install Docker
- Install instructions can be found on the docker website: https://www.docker.com/products/docker-desktop
- Start the *Docker Desktop* program
- Go through their setup to ensure it's working correctly
- You don't need a docker desktop account ( You can skip the last step )

##### Note: You may need to enable visualisation in your bios
https://www.virtualmetric.com/blog/how-to-enable-hardware-virtualization

##### Note: You may also need to install WSL2
https://aka.ms/wslkernel

Restart Docker Desktop once the linux kernel is installed

##### Link Docker and WSL2
https://docs.docker.com/desktop/windows/wsl/

##### 2. Clone Repo
- SSH: ```git clone git@github.com:KurtisPapple/TrapScan-Backend.git```
- HTTP:``git clone https://github.com/KurtisPapple/TrapScan-Backend.git``

If you're struggling with this you can try use the GitHub Desktop program
https://desktop.github.com/

Or clone the repo through VSCode

##### Note: Modify .env file
- Copy the .env.example file to .env
- Update values for your local instance

##### 3. Install PHP & Composer (Windows)
- https://www.sitepoint.com/how-to-install-php-on-windows/
- https://windows.php.net/download/
- https://getcomposer.org/

##### 4. Install PHP & Composer (WSL2)
- Install WSL2 if you haven't (https://aka.ms/wslkernel)
- Open Windows Terminal in your WSL tab (Ctrl + Shift + 3)
- Setup Git SSH Keys
- ``ssh-keygen``
- ``cat ~/.ssh/id_rsa.pub``
- Copy this output
- Open your Profile settings in GitHub (where you'd change your password)
- SSH Keys tab
- New SSH Key
- Paste in your key and give it a title
- ``sudo apt-get update``
- Reclone the repo inside WSL
- ``sudo apt install php``
- ``sudo apt install unzip``
- Install PHP extensions. This depends on the version of PHP you installed. The following packages are needed for PHP8
- ``sudo apt install php8.0-gd php8.0-dom php8.0mbstring``
- Verify the install with ``php --version``
- Install composer with Command-line installation ( https://getcomposer.org/ ) 
- Verify the install with ``composer --verion``
- Setup docker for use with WSL (https://stackoverflow.com/questions/61592709/docker-not-running-on-ubuntu-wsl-cannot-connect-to-the-docker-daemon-at-unix)

##### 3. Run Laravel Sail
https://laravel.com/docs/8.x/sail
- Open the project in VSCode
- Install any recommenced plugins
- mbstring, json, 
- Open a terminal ( https://www.microsoft.com/store/productId/9N0DX20HK701 )
- ``cd trapscan-backend`` Or whatever directory you chose
- ``php artisan sail:install``
- ``composer install``
- ``./vendor/bin/sail up``
###### You may need to restart docker (from your toolbar, clicking the X doesn't close it)

##### 4. Run Migration and Seeders
- Edit ~/.bashrc to add the sail alias
- ``nano ~/.bashrc``
- Add ``alias sail='bash vendor/bin/sail'`` into this file
- ``source ~/.bashrc``
``sail artisan migrate:fresh --seed``

##### 5. Running
http://localhost will be the main app
http://localhost:8090 has a phpmyadmin instance (credentials can be found in your .env)


## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.
