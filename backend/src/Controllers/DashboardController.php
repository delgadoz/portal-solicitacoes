<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\UsuarioAutenticado;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;

final class DashboardController
{
    public static function index(UsuarioAutenticado $usuario): never
    {
        $service = new DashboardService(new DashboardRepository(Database::connection()));
        Response::success($service->obter($usuario));
    }
}
