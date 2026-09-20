<?php
function rastreoEsDemo($token) {
    return getenv('RASTREO_DEMO') === '1' && $token === 'deadbeefdeadbeefdeadbeefdeadbeef';
}
function rastreoDemoPayload() {
    $S = [19.5700, -98.9800];
    $O = [19.4326, -99.1332];
    $D = [19.3700, -99.1900];
    $t = time() % 240;
    $fase = $t < 120 ? 1 : 2;
    $r = pow(0.02, ($t % 120) / 120);
    $A = $fase === 1 ? $S : $O;
    $B = $fase === 1 ? $O : $D;
    $log = ['assignedTime' => 'x', 'startTime' => 'x'];
    if ($fase === 2) $log['pickedUpTime'] = 'x';
    return [
        'estado_liga' => 'en_curso',
        'terminado' => false,
        'mensaje_cierre' => null,
        'orden' => [
            'numero' => 'DEMO-001',
            'estadoAdmin' => null,
            'etaTime' => 'Demo',
            'origen' => ['nombre' => 'Origen demo', 'direccion' => 'Origen demo', 'lat' => $O[0], 'lng' => $O[1]],
            'destino' => ['nombre' => 'Destino demo', 'direccion' => 'Destino demo', 'lat' => $D[0], 'lng' => $D[1]],
            'activityLog' => $log,
        ],
        'chofer' => [
            'nombre' => 'Chofer demo',
            'telefono' => '5555555555',
            'foto' => null,
            'lat' => $B[0] + ($A[0] - $B[0]) * $r,
            'lng' => $B[1] + ($A[1] - $B[1]) * $r,
            'enTurno' => true,
        ],
    ];
}
