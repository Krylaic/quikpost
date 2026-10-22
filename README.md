# Quikpost!
Quikpost is a simple social media website that allows users to share their thoughts quickly.

# How to Setup
Before you set it up, make sure you're running PHP 8, and you have the gd extension enabled. You'll also need a mysql (mariadb) database and composer.

## Step 1
Navigate to your server directory of choice (e.g. C:/xampp/htdocs, /var/www/html) and run
```bash
git clone https://github.com/Krylaic/quikpost.git
cd quikpost
composer install
```
This pretty much just clones the repository and runs composer install to get the necessary dependencies

## Step 2
In the directory you have quikpost in, you will see a file called ".env.example", rename it to JUST ".env", and configure it to work for your server.

## Step 3
Now, navigate to phpmyadmin, create a new database, and import the "quikpost.sql" file into the database.

## Step 4
Edit your virtual host configuration to make sure that it is pointed towards /wherever/quikpost/public, and NOT /wherever/quikpost.

Then you are done ! :D

# Old version (pre-rewrite)
You can see the old quikpost here:
https://github.com/nugg3tphp/quikpost

# AI
I coded this site with as little AI as possible, and for the most part I would say 95% of the website is all code written by me, but some stuff I got Gemini to write for me (mostly in the admin panel), and for some stuff that I just don't understand how to do yet. Some stuff is taken from stack overflow, you'll be able to see the little code comment showing where it was copied from. For the most part, quikpost is almost entirely HUMAN WRITTEN.
