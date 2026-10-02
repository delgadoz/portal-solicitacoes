<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Repositories\CategoriaRepository;

final class CategoriaController
{
    public static function index(): never
    {
        Response::success((new CategoriaRepository(Database::connection()))->listar());
    }
}
