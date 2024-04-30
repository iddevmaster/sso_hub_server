<?php

namespace App\Models;

use Laravel\Passport\Client;

class Passport extends Client {
    public function skipsAuthorization() {
        // false: All the clients should stop for authorization
        return false;
    }
}
