<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): void
    {
        $pathExists = false;

        foreach ($this->routes as [$method, $pattern, $handler]) {
            if (!preg_match($pattern, $request->path, $matches)) {
                continue;
            }

            $pathExists = true;

            if ($method !== $request->method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($request, $params);
            return;
        }

        if ($pathExists) {
            throw new HttpException(405, 'METHOD_NOT_ALLOWED', 'Método não permitido para esta rota.');
        }

        throw new NotFoundException('Rota não encontrada.');
    }

    private function add(string $method, string $path, callable $handler): void
    {
        // Converte /api/solicitacoes/{id} em uma regex com grupo nomeado "id"
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = [$method, $pattern, $handler];
    }
}
