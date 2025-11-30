<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe fourni est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',

    // Custom authentication messages
    'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    'unauthenticated' => 'Veuillez vous connecter pour continuer.',
    'token_expired' => 'Votre session a expiré. Veuillez vous reconnecter.',
    'token_invalid' => 'Jeton d\'authentification invalide.',
    'account_disabled' => 'Votre compte a été désactivé.',
    'email_not_verified' => 'Veuillez vérifier votre adresse e-mail.',
    'logout_success' => 'Vous avez été déconnecté avec succès.',
    'login_success' => 'Connexion réussie.',
];
