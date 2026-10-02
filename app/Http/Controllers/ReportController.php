<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
public function clientesPorZona()
    {
        $totalGeneral = DB::table('clients')->count();

        $zonas = DB::table('clients')
            ->select('zona-geografica', DB::raw('count(*) as total'))
            ->groupBy('zona-geografica')
            ->get();


        $zonasConPorcentaje = $zonas->map(function ($zona) use ($totalGeneral) {
            $zona->porcentaje = $totalGeneral > 0 
                ? round(($zona->total / $totalGeneral) * 100, 2) 
                : 0;
            return $zona;
        });

        // Aqui tienes el codigo de modificacion de filtrar solo las zonas con más del 15% del total

        $zonasConPorcentaje = $zonasConPorcentaje->filter(function ($zona) {
            return $zona->porcentaje > 15;
        });

        $labels = $zonasConPorcentaje->pluck('zona-geografica');
        $data = $zonasConPorcentaje->pluck('total');

        return view('reports.zonas', compact('totalGeneral', 'zonasConPorcentaje', 'labels', 'data'));
    }

    public function interaccionesPorAsesor()
    {
        $asesores = DB::table('users_simple')
            ->select(
                'users_simple.id',
                'users_simple.name',
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'Llamada' THEN 1 END) AS llamadas"),
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'Visita' THEN 1 END) AS visitas"),
                DB::raw("COUNT(CASE WHEN interactions.tipo_interaccion = 'WhatsApp' THEN 1 END) AS whatsapp"),
                DB::raw('COUNT(interactions.id) AS total')
            )
            ->leftJoin('clients', 'clients.user_id', '=', 'users_simple.id')
            ->leftJoin('interactions', 'interactions.client_id', '=', 'clients.id')
            ->groupBy('users_simple.id', 'users_simple.name')
            ->orderByDesc('total')
            ->get();

        $labels = $asesores->pluck('name')->toArray();
        $llamadas = $asesores->pluck('llamadas')->toArray();
        $visitas = $asesores->pluck('visitas')->toArray();
        $whatsapp = $asesores->pluck('whatsapp')->toArray();

        return view('reports.interacciones', compact(
            'asesores', 'labels', 'llamadas', 'visitas', 'whatsapp'
        ));
    }

    public function interaccionesPorDia()
    {
    $interaccionesPorDia = DB::table('interactions')
        ->select(
            DB::raw('DATE(fecha_seguimiento) as fecha'),
            DB::raw('COUNT(*) as total')
        )
        ->groupBy(DB::raw('DATE(fecha_seguimiento)'))
        ->orderBy('fecha', 'asc')
        ->get();

    return view('reports.interacciones_dia', compact('interaccionesPorDia'));
    }
}