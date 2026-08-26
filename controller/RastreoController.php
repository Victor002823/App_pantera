<?php
require_once __DIR__ . '/../model/RastreoModel.php';

class RastreoController
{
    private RastreoModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new RastreoModel($db);
    }

    public function generarLigaParaServicio(int $shipdayOrderId, ?int $servicioId, ?string $clienteNombre): array
    {
        return $this->model->crearLiga($shipdayOrderId, $servicioId, $clienteNombre);
    }

    public function mostrarPagina(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            http_response_code(404);
            require __DIR__ . '/../view/rastreo_no_encontrado.php';
            return;
        }

        $liga = $this->model->obtenerLigaPorToken($token);

        if (!$liga) {
            http_response_code(404);
            require __DIR__ . '/../view/rastreo_no_encontrado.php';
            return;
        }
        if (!$this->model->esLigaValida($liga)) {
            header('Location: https://mudanzasellince.com/');
            exit;
        }

        // Consultamos la orden UNA sola vez aquí; la vista ya no vuelve a hacerlo.
        $ordenData = $this->model->obtenerOrden((int)$liga['shipday_order_id']);
        $terminado = false;

        if ($ordenData) {
            $evaluacion = $this->model->evaluarEstadoOrden($ordenData);
            if ($evaluacion['terminado']) {
                $terminado = true;
                try {
                    $this->model->marcarEstado((int)$liga['id'], $evaluacion['estado']);
                } catch (Exception $e) {
                    // Silenciar error en BD
                }
            }
        }

        if ($terminado) {
            header('Location: https://mudanzasellince.com/');
            exit;
        }

        $tokenParaVista = $token;
        $ordenDataParaVista = $ordenData;
        require __DIR__ . '/../view/rastreo_publico.php';
    }
}
