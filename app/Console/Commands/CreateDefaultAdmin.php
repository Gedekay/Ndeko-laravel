<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateDefaultAdmin extends Command
{
    protected $signature = 'create-admin';

    protected $description = 'Créer un administrateur par défaut';

    public function handle()
    {
        $phone = $this->ask('Entrez le numéro de téléphone admin');
        $fullname = $this->ask('Entrez le nom complet admin');
        $password = $this->secret('Entrez le mot de passe admin');

        if (!$phone || !$fullname || !$password) {
            $this->error('Tous les champs sont obligatoires.');
            return Command::FAILURE;
        }

        $exists = User::where('phone', $phone)->first();

        if ($exists) {
            $this->error('Un utilisateur avec ce numéro existe déjà.');
            return Command::FAILURE;
        }

        $admin = User::create([
            'fullname' => $fullname,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_blocked' => false,
            'is_online' => false,
            'last_seen' => now(),
        ]);

        $this->info(' Admin créé avec succès !');
        $this->line('ID: ' . $admin->id);
        $this->line('Nom: ' . $admin->fullname);
        $this->line('Téléphone: ' . $admin->phone);

        return Command::SUCCESS;
    }
}