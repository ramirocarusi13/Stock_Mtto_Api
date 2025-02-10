<?php

return [
    'paths' => ['api/*'], // Define las rutas donde se aplicará CORS
    'allowed_methods' => ['*'], // Permite todos los métodos (GET, POST, PUT, etc.)
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Permite todos los encabezados
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // Cambiar a true si utilizas cookies o autenticación basada en sesión
];
