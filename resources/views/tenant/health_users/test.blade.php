@extends('tenant.layouts.app')

@section('title', 'Usuarios Sector Salud - Test')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-user-md text-primary"></i>
                    Usuarios Sector Salud - Versión Test
                </h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Estado del Sistema -->
        <div class="alert alert-success">
            <h4><i class="fas fa-check-circle"></i> Sistema Funcionando</h4>
            <p>La vista está cargando correctamente.</p>
            <p><strong>Total de usuarios:</strong> {{ $count ?? 0 }}</p>
            <p><strong>Registros hoy:</strong> {{ $todayCount ?? 0 }}</p>
        </div>

        <!-- Botones de Prueba -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pruebas de Funcionalidad</h3>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-primary" onclick="testDataEndpoint()">
                    <i class="fas fa-database"></i> Probar Endpoint de Datos
                </button>
                
                <button type="button" class="btn btn-info" onclick="testSearchEndpoint()">
                    <i class="fas fa-search"></i> Probar Búsqueda
                </button>
                
                <div id="test-results" class="mt-3"></div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
function testDataEndpoint() {
    $('#test-results').html('<div class="alert alert-info">Probando endpoint de datos...</div>');
    
    $.ajax({
        url: '/health-users/data',
        method: 'GET',
        success: function(response) {
            $('#test-results').html(`
                <div class="alert alert-success">
                    <h5>✅ Endpoint de datos funciona correctamente</h5>
                    <p><strong>Total de registros:</strong> ${response.total || 0}</p>
                    <p><strong>Datos recibidos:</strong> ${response.data ? response.data.length : 0} usuarios</p>
                    <p><strong>Página actual:</strong> ${response.current_page || 1}</p>
                </div>
            `);
        },
        error: function(xhr) {
            $('#test-results').html(`
                <div class="alert alert-danger">
                    <h5>❌ Error en endpoint de datos</h5>
                    <p><strong>Status:</strong> ${xhr.status}</p>
                    <p><strong>Error:</strong> ${xhr.responseText || 'Error desconocido'}</p>
                </div>
            `);
        }
    });
}

function testSearchEndpoint() {
    $('#test-results').html('<div class="alert alert-info">Probando endpoint de búsqueda...</div>');
    
    $.ajax({
        url: '/health-users/find-user-by-document?document=123456789',
        method: 'GET',
        success: function(response) {
            $('#test-results').html(`
                <div class="alert alert-success">
                    <h5>✅ Endpoint de búsqueda funciona</h5>
                    <p><strong>Respuesta:</strong> ${response.message || 'Usuario encontrado'}</p>
                </div>
            `);
        },
        error: function(xhr) {
            if(xhr.status === 404) {
                $('#test-results').html(`
                    <div class="alert alert-warning">
                        <h5>✅ Endpoint de búsqueda funciona (usuario no encontrado)</h5>
                        <p>El endpoint responde correctamente cuando no encuentra usuarios.</p>
                    </div>
                `);
            } else {
                $('#test-results').html(`
                    <div class="alert alert-danger">
                        <h5>❌ Error en endpoint de búsqueda</h5>
                        <p><strong>Status:</strong> ${xhr.status}</p>
                        <p><strong>Error:</strong> ${xhr.responseText || 'Error desconocido'}</p>
                    </div>
                `);
            }
        }
    });
}
</script>
@endsection
