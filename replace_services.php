<?php
$file_path = 'includes/services.php';
$content = file_get_contents($file_path);

$replacements = [
    "'title'       => 'MeTransfers Barcelona - Traslado al Aeropuerto desde Barcelona'," => "'title'       => 'MeTransfers Barcelona - Traslado al Aeropuerto desde Barcelona',\n\t\t\t'h1'          => 'Traslado privado al Aeropuerto de Barcelona',",
    "'title'       => 'MeTransfers Barcelona - Traslado al Puerto de Barcelona desde la ciudad'," => "'title'       => 'MeTransfers Barcelona - Traslado al Puerto de Barcelona desde la ciudad',\n\t\t\t'h1'          => 'Traslado privado al Puerto de Barcelona',",
    "'title'       => 'MeTransfers Barcelona - Chófer Privado por Horas en Barcelona'," => "'title'       => 'MeTransfers Barcelona - Chófer Privado por Horas en Barcelona',\n\t\t\t'h1'          => 'Chófer privado por horas en Barcelona',",
    "'title'       => 'MeTransfers Barcelona - Transporte Corporativo y Eventos desde Barcelona'," => "'title'       => 'MeTransfers Barcelona - Transporte Corporativo y Eventos desde Barcelona',\n\t\t\t'h1'          => 'Transporte corporativo y eventos en Barcelona',",
    "'title'       => 'MeTransfers Barcelona - Tours Privados desde Barcelona'," => "'title'       => 'MeTransfers Barcelona - Tours Privados desde Barcelona',\n\t\t\t'h1'          => 'Tours privados desde Barcelona',",
    "'title'       => 'MeTransfers Barcelona - Transporte para Grupos desde Barcelona'," => "'title'       => 'MeTransfers Barcelona - Transporte para Grupos desde Barcelona',\n\t\t\t'h1'          => 'Transporte para grupos y celebraciones',",

    'hasta 60 min de espera gratuita' => 'hasta 60 min de cortesía en aeropuerto',
    '60 minutos de espera gratuita' => '60 minutos de cortesía en aeropuerto',
    '60 min de espera' => 'Espera de cortesía',
    'Incluimos hasta 60 minutos de espera gratuita en llegadas internacionales.' => 'Incluimos tiempo de cortesía en llegadas al aeropuerto.',
    
    'puntualidad garantizada' => 'máxima puntualidad',
    'Confirmación en minutos' => 'Confirmación rápida',
    
    'Wi-Fi y agua a bordo' => 'Wi-Fi y agua a bordo (según vehículo)',
    'Disponible 24/7' => 'Disponibilidad 24/7 (bajo reserva)',
    'vehículo Mercedes' => 'vehículo premium',
    'Flota Mercedes Premium' => 'Flota Premium',
    'flota de vehículos Mercedes' => 'flota de vehículos premium',
    'Vehículos Mercedes seleccionados' => 'Vehículos premium seleccionados',
    'Vehículos Mercedes' => 'Vehículos premium',
    'chófer uniformado y bilingüe' => 'chófer uniformado (idiomas bajo petición)',
    'Conductores bilingües' => 'Conductores con idiomas (bajo petición)',
    'Cancelación gratuita hasta 24 horas antes' => 'Cancelación sujeta a condiciones',
    'Cancelación gratuita hasta 24 h antes' => 'Cancelación sujeta a condiciones',
    'MeTransfers ofrece cancelación gratuita hasta 24 horas antes del servicio, salvo condiciones diferentes indicadas en reservas especiales.' => 'MeTransfers ofrece flexibilidad de cancelación según el servicio contratado. Consulta las condiciones en tu reserva.',
    'Atención personalizada 24/7' => 'Atención 24/7 bajo reserva',
    'Atención 24/7' => 'Atención 24/7 (bajo reserva)'
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);
file_put_contents($file_path, $content);
echo "Done";
