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

##### 2. Clone Repo
- SSH: ```git clone git@github.com:KurtisPapple/TrapScan-Backend.git```
- HTTP:``git clone https://github.com/KurtisPapple/TrapScan-Backend.git``

If you're struggling with this you can try use the GitHub Desktop program
https://desktop.github.com/

Or clone the repo through VSCode

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
- Install PHP extensions ``sudo apt install php7.4-common php7.4-bcmath openssl php7.4-json php7.4-mbstring``
- ``sudo apt install php7.4-opcache php7.4-pdo php7.4-bcmath php7.4-calendar php7.4-ctype php7.4-exif php7.4-ffi php7.4-fileinfo php7.4-ftp php7.4-gettext php7.4-iconv php7.4-js
    on php7.4-mbstring php7.4-phar php7.4-posix php7.4-readline php7.4-shmop php7.4-sockets php7.4-sysvmsg php7.4-sy
    svsem php7.4-sysvshm php7.4-tokenizer php7.4-gd php7.4-xml``
- Verify the install with ``php --version``
- Install composer with Command-line installation ( https://getcomposer.org/ ) 
- Verify the install with ``composer --verion``
- Setup docker for use with WSL (https://stackoverflow.com/questions/61592709/docker-not-running-on-ubuntu-wsl-cannot-connect-to-the-docker-daemon-at-unix)

##### 3. Run Laravel Sail
https://laravel.com/docs/8.x/sail
- Open the project in VSCode
- Install any recommenced plugins
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
