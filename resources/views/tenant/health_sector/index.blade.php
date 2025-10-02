@extends('tenant.layouts.app')

@section('content')
    <div class="page-header pr-0">
        <div class="d-flex align-items-center">
            <h2 class="page-header-title">{{ __('Carga Masiva Sector Salud') }}</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Facturación Electrónica - Sector Salud') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Búsqueda de Usuario por Cédula -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h4 class="card-title text-white">{{ __('Búsqueda de Usuario') }}</h4>
                                </div>
                                <div class="card-body">
                                    <form id="searchUserForm">
                                        <div class="form-group">
                                            <label for="documento">{{ __('Número de Cédula') }}</label>
                                            <input type="text" class="form-control" id="documento" name="documento" 
                                                   placeholder="Ingrese el número de cédula" maxlength="20">
                                        </div>
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fa fa-search"></i> {{ __('Buscar Usuario') }}
                                        </button>
                                    </form>
                                    
                                    <!-- Resultado de búsqueda -->
                                    <div id="userResult" class="mt-3" style="display: none;">
                                        <div class="alert alert-success">
                                            <h5><i class="fa fa-user"></i> {{ __('Usuario Encontrado') }}</h5>
                                            <div id="userInfo"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Carga Masiva de Excel -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success">
                                    <h4 class="card-title text-white">{{ __('C.M SECTOR SALUD') }}</h4>
                                </div>
                                <div class="card-body">
                                    <form id="importExcelForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group">
                                            <label for="excel_file">{{ __('Archivo Excel') }}</label>
                                            <input type="file" class="form-control-file" id="excel_file" name="excel_file" 
                                                   accept=".xlsx,.xls,.csv" required>
                                            <small class="form-text text-muted">
                                                {{ __('Formatos permitidos: .xlsx, .xls, .csv') }}
                                            </small>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fa fa-upload"></i> {{ __('C.M SECTOR SALUD') }}
                                        </button>
                                    </form>
                                    
                                    <!-- Progreso de importación -->
                                    <div id="importProgress" class="mt-3" style="display: none;">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-animated progress-bar-striped" 
                                                 role="progressbar" style="width: 100%">
                                                {{ __('Procesando...') }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Resultado de importación -->
                                    <div id="importResult" class="mt-3" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enlaces de navegación -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="{{ route('tenant.health_sector.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> {{ __('Nueva Factura') }}
                                </a>
                                <a href="{{ route('tenant.health_sector.users') }}" class="btn btn-info">
                                    <i class="fa fa-users"></i> {{ __('Ver Usuarios') }}
                                </a>
                                <a href="{{ route('tenant.health_sector.invoices') }}" class="btn btn-warning">
                                    <i class="fa fa-file-invoice"></i> {{ __('Ver Facturas') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Búsqueda de usuario
    $('#searchUserForm').submit(function(e) {
        e.preventDefault();
        
        const documento = $('#documento').val();
        if (!documento) {
            alert('Por favor ingrese un número de cédula');
            return;
        }

        $.get('{{ route("tenant.health_sector.search_user") }}', {documento: documento})
            .done(function(response) {
                if (response.success) {
                    const user = response.user;
                    let userHtml = `
                        <strong>Nombre:</strong> ${user.nombre_completo}<br>
                        <strong>Documento:</strong> ${user.documento}<br>
                        <strong>Teléfono:</strong> ${user.telefono || user.celular || 'N/A'}<br>
                        <strong>Email:</strong> ${user.email || 'N/A'}<br>
                        <strong>EPS:</strong> ${user.eps_nombre || 'N/A'}<br>
                        <strong>Dirección:</strong> ${user.direccion || 'N/A'}
                    `;
                    $('#userInfo').html(userHtml);
                    $('#userResult').show();
                } else {
                    alert('Usuario no encontrado');
                    $('#userResult').hide();
                }
            })
            .fail(function() {
                alert('Error en la búsqueda');
                $('#userResult').hide();
            });
    });

    // Importación de Excel
    $('#importExcelForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $('#importProgress').show();
        $('#importResult').hide();

        $.ajax({
            url: '{{ route("tenant.health_sector.import_excel") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#importProgress').hide();
                
                if (response.success) {
                    $('#importResult').html(`
                        <div class="alert alert-success">
                            <h5><i class="fa fa-check"></i> ${response.message}</h5>
                            <div>${JSON.stringify(response.stats)}</div>
                        </div>
                    `).show();
                } else {
                    $('#importResult').html(`
                        <div class="alert alert-danger">
                            <h5><i class="fa fa-times"></i> Error</h5>
                            <div>${response.message}</div>
                        </div>
                    `).show();
                }
            },
            error: function(xhr) {
                $('#importProgress').hide();
                const response = xhr.responseJSON;
                $('#importResult').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fa fa-times"></i> Error</h5>
                        <div>${response.message || 'Error desconocido'}</div>
                    </div>
                `).show();
            }
        });
    });
});
</script>
@endsection
