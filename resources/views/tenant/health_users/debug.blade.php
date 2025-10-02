<!DOCTYPE html>
<html>
<head>
    <title>Debug Health Users</title>
</head>
<body>
    <h1>Debug Health Users</h1>
    <p>Vista cargada correctamente: {{ date('Y-m-d H:i:s') }}</p>
    
    <h3>Test de modelo:</h3>
    <?php
    try {
        $count = App\Models\Tenant\TenancyHealthUser::count();
        echo "<p>✅ Usuarios encontrados: $count</p>";
        
        $user = App\Models\Tenant\TenancyHealthUser::first();
        if ($user) {
            echo "<p>✅ Primer usuario: {$user->primer_nombre} {$user->primer_apellido} (Doc: {$user->documento})</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h3>Test de ruta:</h3>
    <p>URL actual: {{ Request::url() }}</p>
    <p>Ruta: {{ Route::currentRouteName() }}</p>
</body>
</html>
