###### INTRO
These sources we are using for https://myexplorer.wrkz.work/#getwrkz. The basic requirement is a webserver with php php-curl php-mysqli and **MySQL** server, **Wrkzd** & **walletd** to feed faucet's wallet.

###### INSTALLATION
1. MySQL table template is available within this repo. It shall be imported manually to MySQL database.
2. Copy the three files to the appropriate of wrkzcoin-explorer
3. Edit index.html and add new menu:

`				<li><a class="hot_link" data-page="freewrkz.php" href="#getwrkz">
                    <i class="fa fa-money" aria-hidden="true"></i> Wrkz Faucet
                </a></li>`

4. Edit **config.php** to fit your needs and MySQL database information, as well as walletd that is running.
4. Testing and there it is.
