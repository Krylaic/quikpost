<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    // i got gemini to write the schema here from a .sql file

    public function change(): void
    {
        $audit = $this->table('audit', ['collation' => 'utf8mb4_general_ci']);
        $audit->addColumn('action', 'string', ['limit' => 255])
              ->addColumn('admin', 'string', ['limit' => 255])
              ->addColumn('date', 'datetime')
              ->create();

        $contact = $this->table('contact', ['collation' => 'utf8mb4_general_ci']);
        $contact->addColumn('content', 'text', ['limit' => 10000])
                ->addColumn('email', 'string', ['limit' => 255])
                ->addColumn('contact_date', 'datetime', ['null' => true])
                ->create();

        $emails = $this->table('emails', ['collation' => 'utf8mb4_general_ci']);
        $emails->addColumn('destination', 'string', ['limit' => 255])
               ->addColumn('content', 'text', ['limit' => 10000])
               ->addColumn('date_sent', 'datetime')
               ->create();

        $followers = $this->table('followers', ['collation' => 'utf8mb4_general_ci']);
        $followers->addColumn('follower', 'string', ['limit' => 255])
                  ->addColumn('following', 'string', ['limit' => 255])
                  ->create();

        $ipAddresses = $this->table('ip_addresses', ['collation' => 'utf8mb4_general_ci']);
        $ipAddresses->addColumn('address', 'string', ['limit' => 255])
                    ->addColumn('user_id', 'integer')
                    ->create();

        $likes = $this->table('likes', ['collation' => 'utf8mb4_general_ci']);
        $likes->addColumn('post_id', 'integer')
              ->addColumn('user_id', 'integer')
              ->create();

        $news = $this->table('news', ['collation' => 'utf8mb4_general_ci']);
        $news->addColumn('title', 'string', ['limit' => 255])
             ->addColumn('content', 'text')
             ->addColumn('author', 'string', ['limit' => 20])
             ->addColumn('post_date', 'datetime')
             ->create();

        $options = $this->table('options', ['collation' => 'utf8mb4_general_ci']);
        $options->addColumn('name', 'string', ['limit' => 255])
                ->addColumn('slogan', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('domain', 'string', ['limit' => 255])
                ->addColumn('about', 'text', ['null' => true])
                ->addColumn('tos', 'text', ['null' => true])
                ->addColumn('privacy_policy', 'text', ['null' => true])
                ->addColumn('community_guidelines', 'text', ['null' => true])
                ->addColumn('ip_check', 'boolean', ['default' => 1])
                ->create();

        if ($this->isMigratingUp()) {
            $optionsData = [
                [
                    'id' => 1,
                    'name' => 'Quikpost',
                    'slogan' => 'Share your Thoughts',
                    'domain' => 'localhost',
                    'about' => 'Quikpost is a simple social media website that allows users to share their thoughts quickly.',
                    'tos' => "Last updated: 10/06/2026\r\n\r\n\"We\", \"our\", \"us\", \"the site\", and \"our site\" refers to this website, Quikpost, and/or the people running it.\r\n\r\nBy using our website, you agree to these terms of service.\r\n\r\nWhile Quikpost is open source, these terms apply specifically to this hosted instance of Quikpost.\r\n\r\nWhen registering for the site, you agree that you are providing accurate information and that you are the age of 13 or older.\r\n\r\nWe have the right to terminate or suspend your account at any time for any reason.\r\n\r\nOur site is provided \"as is\" without any warranties of any kind.\r\n\r\nWe may update these terms of service occasionally.",
                    'privacy_policy' => "Last updated: 10/06/2026\r\n\r\nWe collect only the necessary amount of data for our site to function.\r\n\r\nThe information we collect includes your username, email address (for verification), a hash of your password, the date you joined the website, all IP addresses you have used on the site (for spam prevention), and any additional information you decide to add to your profile, such as the location you set, your biography, and Last.FM username.\r\n\r\nOur site is proxied through Cloudflare, which means they may collect certain information that we do not have access to.\r\n\r\nWe process this data based on your consent when you register for an account, and you have the right to view, modify, or remove your information at any time, and you can contact us <a href=\"/contact\">here</a>.\r\n\r\nWe may update this privacy policy occasionally.",
                    'community_guidelines' => 'Still working on these',
                    'ip_check' => 1,
                ]
            ];
            $this->table('options')->insert($optionsData)->saveData();
        }

        $posts = $this->table('posts', ['collation' => 'utf8mb4_general_ci']);
        $posts->addColumn('content', 'text')
              ->addColumn('author', 'string', ['limit' => 255])
              ->addColumn('post_date', 'datetime')
              ->addColumn('reply_to', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('attachment', 'string', ['limit' => 255, 'null' => true])
              ->create();

        $reports = $this->table('reports', ['collation' => 'utf8mb4_general_ci']);
        $reports->addColumn('content', 'string', ['limit' => 1000])
                ->addColumn('post_id', 'integer')
                ->addColumn('user_id', 'integer')
                ->addColumn('report_date', 'datetime')
                ->create();

        $users = $this->table('users', ['collation' => 'utf8mb4_general_ci']);
        $users->addColumn('username', 'string', ['limit' => 20])
              ->addColumn('password', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('display_name', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('avatar', 'string', ['limit' => 255, 'default' => '/dynamic/avatars/default.jpg'])
              ->addColumn('banner', 'string', ['limit' => 255, 'default' => '/dynamic/banners/default.jpg'])
              ->addColumn('suspended', 'boolean', ['default' => 0])
              ->addColumn('admin_level', 'integer', ['default' => 0])
              ->addColumn('bio', 'string', ['limit' => 1000, 'null' => true])
              ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('join_date', 'datetime')
              ->addColumn('verified', 'boolean', ['default' => 0])
              ->addColumn('verify_token', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('reset_token', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('lastfm_username', 'string', ['limit' => 255, 'null' => true])
              ->create();
    }
}