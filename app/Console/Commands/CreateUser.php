<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "create-user";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new user for the application";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Creating a new user...");

        $name = $this->ask("What is your name?");
        $email = $this->ask("What is your email address?");
        $password = $this->secret("What is the password?");

        if ($this->confirm("Are you sure you want to create a new user?")) {
            $user = \App\Models\User::create([
                "name" => $name,
                "email" => $email,
                "password" => bcrypt($password),
            ]);

            $this->info("User created successfully.");
        } else {
            $this->info("User creation cancelled.");
        }
    }
}
