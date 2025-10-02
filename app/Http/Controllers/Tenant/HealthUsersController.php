<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenancyHealthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DataTables;

class HealthUsersController extends Controller
{
    public function index()
    {
        try {
            // Obtener usuarios con paginación simple
            $users = TenancyHealthUser::select([
                'id', 'documento', 'tipo_documento', 
                'primer_nombre', 'segundo_nombre', 
                'primer_apellido', 'segundo_apellido',
                'nombre_completo', 'telefono', 'email'
            ])
            ->orderBy('id', 'desc')
            ->paginate(20);

            // Usar vista simple
            return view('tenant.health_users.simple', compact('users'));
            
        } catch (\Exception $e) {
            // Si hay error, mostrar mensaje claro
            $emptyUsers = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            return view('tenant.health_users.simple', [
                'users' => $emptyUsers,
                'error' => 'Error al cargar usuarios: ' . $e->getMessage()
            ]);
        }
    }

    public function data(Request $request)
    {
        try {
            $users = TenancyHealthUser::select([
                'id',
                'documento',
                'tipo_documento',
                'primer_nombre',
                'segundo_nombre',
                'primer_apellido',
                'segundo_apellido',
                'nombre_completo',
                'telefono',
                'email',
                'eps_nombre',
                'municipio',
                'created_at'
            ])->paginate(15);

            $data = [];
            foreach ($users as $user) {
                // Construir nombre completo si no existe
                $nombreCompleto = $user->nombre_completo;
                if (!$nombreCompleto) {
                    $nombres = array_filter([
                        $user->primer_nombre,
                        $user->segundo_nombre,
                        $user->primer_apellido,
                        $user->segundo_apellido
                    ]);
                    $nombreCompleto = implode(' ', $nombres);
                }

                $data[] = [
                    'id' => $user->id,
                    'documento' => $user->documento,
                    'tipo_documento' => $user->tipo_documento,
                    'nombre_completo' => $nombreCompleto ?: 'Sin nombre',
                    'telefono' => $user->telefono ?: '-',
                    'email' => $user->email ?: '-',
                    'eps_nombre' => $user->eps_nombre ?: '-',
                    'municipio' => $user->municipio ?: '-',
                    'created_at' => $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-',
                    'actions' => '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info btn-sm" onclick="viewUser(' . $user->id . ')" title="Ver">
                                <i class="fa fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="editUser(' . $user->id . ')" title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(' . $user->id . ')" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    '
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'prev_page_url' => $users->previousPageUrl(),
                    'next_page_url' => $users->nextPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function create()
    {
        return view('tenant.health_users.create');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'documento' => 'required|unique:tenancy_health_users,documento',
                'tipo_documento' => 'required',
                'primer_nombre' => 'required|string|max:100',
                'primer_apellido' => 'required|string|max:100',
                'segundo_nombre' => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:150',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = new TenancyHealthUser();
            $user->documento = $request->documento;
            $user->tipo_documento = $request->tipo_documento;
            $user->primer_nombre = $request->primer_nombre;
            $user->segundo_nombre = $request->segundo_nombre;
            $user->primer_apellido = $request->primer_apellido;
            $user->segundo_apellido = $request->segundo_apellido;
            $user->telefono = $request->telefono;
            $user->email = $request->email;
            
            // Construir nombre completo
            $nombre_completo = trim($request->primer_nombre . ' ' . 
                                    ($request->segundo_nombre ?? '') . ' ' . 
                                    $request->primer_apellido . ' ' . 
                                    ($request->segundo_apellido ?? ''));
            $user->nombre_completo = $nombre_completo;
            $user->created_by = auth()->user()->email ?? 'sistema';
            $user->activo = true;
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'user' => $user
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = TenancyHealthUser::findOrFail($id);
        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function edit($id)
    {
        $user = TenancyHealthUser::findOrFail($id);
        return view('tenant.health_users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        try {
            $user = TenancyHealthUser::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'documento' => 'required|unique:tenancy_health_users,documento,' . $id,
                'tipo_documento' => 'required',
                'primer_nombre' => 'required|string|max:100',
                'primer_apellido' => 'required|string|max:100',
                'segundo_nombre' => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:150',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->documento = $request->documento;
            $user->tipo_documento = $request->tipo_documento;
            $user->primer_nombre = $request->primer_nombre;
            $user->segundo_nombre = $request->segundo_nombre;
            $user->primer_apellido = $request->primer_apellido;
            $user->segundo_apellido = $request->segundo_apellido;
            $user->telefono = $request->telefono;
            $user->email = $request->email;
            
            // Reconstruir nombre completo
            $nombre_completo = trim($request->primer_nombre . ' ' . 
                                    ($request->segundo_nombre ?? '') . ' ' . 
                                    $request->primer_apellido . ' ' . 
                                    ($request->segundo_apellido ?? ''));
            $user->nombre_completo = $nombre_completo;
            $user->updated_by = auth()->user()->email ?? 'sistema';
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = TenancyHealthUser::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario: ' . $e->getMessage()
            ]);
        }
    }

    public function search(Request $request)
    {
        $term = $request->get('term');
        
        $users = TenancyHealthUser::where('documento', 'like', "%{$term}%")
            ->orWhere('primer_nombre', 'like', "%{$term}%")
            ->orWhere('primer_apellido', 'like', "%{$term}%")
            ->limit(10)
            ->get();
            
        return response()->json($users);
    }

    public function findByDocument(Request $request)
    {
        $documento = $request->get('documento');
        $tipo_documento = $request->get('tipo_documento');
        
        if (!$documento || !$tipo_documento) {
            return response()->json(['found' => false, 'message' => 'Documento y tipo de documento son requeridos']);
        }

        $user = TenancyHealthUser::where('documento', $documento)
            ->where('tipo_documento', $tipo_documento)
            ->first();
            
        if ($user) {
            return response()->json([
                'found' => true,
                'user' => [
                    'identification_number' => $user->documento,
                    'first_name' => $user->primer_nombre,
                    'middle_name' => $user->segundo_nombre,
                    'surname' => $user->primer_apellido,
                    'second_surname' => $user->segundo_apellido,
                    'phone' => $user->telefono,
                    'email' => $user->email,
                    'birth_date' => $user->fecha_nacimiento,
                    'gender' => $user->genero,
                    'eps_name' => $user->eps_nombre,
                    'eps_code' => $user->eps_codigo,
                    'municipality' => $user->municipio,
                    'address' => $user->direccion
                ]
            ]);
        }

        return response()->json(['found' => false, 'message' => 'Usuario no encontrado']);
    }

    // Nuevo método para buscar usuarios completos de S.S
    public function findUserSS(Request $request)
    {
        $documento = $request->get('documento');
        $tipo_documento = $request->get('tipo_documento');
        
        if (!$documento) {
            return response()->json([
                'found' => false, 
                'message' => 'El número de documento es requerido',
                'debug' => [
                    'documento_buscado' => $documento,
                    'tipo_documento_buscado' => $tipo_documento
                ]
            ]);
        }

        try {
            // Buscar usuario por documento y tipo de documento
            $query = TenancyHealthUser::where('documento', $documento);
            
            if ($tipo_documento) {
                $query->where('tipo_documento', $tipo_documento);
            }
            
            $user = $query->first();
            
            if ($user) {
                return response()->json([
                    'found' => true,
                    'user' => $user->toArray(),
                    'message' => 'Usuario encontrado en base de datos S.S'
                ]);
            }
            
            // Si no encuentra con tipo específico, buscar solo por documento
            $usersByDocument = TenancyHealthUser::where('documento', $documento)->get();
            
            $debugInfo = [
                'documento_buscado' => $documento,
                'tipo_documento_buscado' => $tipo_documento,
                'total_usuarios' => TenancyHealthUser::count(),
                'usuarios_mismo_documento' => $usersByDocument->map(function($u) {
                    return [
                        'id' => $u->id,
                        'documento' => $u->documento,
                        'tipo_documento' => $u->tipo_documento,
                        'nombre_completo' => $u->nombre_completo
                    ];
                })
            ];
            
            return response()->json([
                'found' => false,
                'message' => 'Usuario no encontrado en la base de datos de usuarios S.S',
                'debug' => $debugInfo
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'found' => false,
                'message' => 'Error al buscar el usuario: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]
            ], 500);
        }
    }

    public function export()
    {
        $users = TenancyHealthUser::all();
        
        $headers = [
            'Content-Type' => 'application/json',
        ];

        return response()->json($users, 200, $headers);
    }
}
