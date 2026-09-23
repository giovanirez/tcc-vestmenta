<?php

use App\Middleware\Cors;
use App\Router;
use App\Support\Response;

// Autoload manual (sem Composer): App\Pasta\Classe -> src/Pasta/Classe.php
spl_autoload_register(function (string $classe) {
    if (!str_starts_with($classe, 'App\\')) {
        return;
    }
    $caminhoRelativo = str_replace('\\', '/', substr($classe, strlen('App\\')));
    require __DIR__ . '/../src/' . $caminhoRelativo . '.php';
});

// Nunca mostra erro do PHP cru pro cliente (podia vazar caminho de
// arquivo, versão, etc.) — em produção isso também vai pra um log,
// não pra tela.
ini_set('display_errors', '0');
error_reporting(E_ALL);

Cors::aplicar();

$rotas = new Router();

$rotas->post('/auth/login', [\App\Controllers\AuthController::class, 'login']);

$rotas->get('/usuarios', [\App\Controllers\UsuariosController::class, 'listar']);
$rotas->post('/usuarios', [\App\Controllers\UsuariosController::class, 'criar']);
$rotas->put('/usuarios/{id}', [\App\Controllers\UsuariosController::class, 'atualizar']);
$rotas->patch('/usuarios/{id}/senha', [\App\Controllers\UsuariosController::class, 'redefinirSenha']);
$rotas->patch('/usuarios/{id}/ativo', [\App\Controllers\UsuariosController::class, 'alternarAtivo']);

$rotas->get('/categorias', [\App\Controllers\CategoriasController::class, 'listar']);

$rotas->get('/fornecedores', [\App\Controllers\FornecedoresController::class, 'listar']);
$rotas->post('/fornecedores', [\App\Controllers\FornecedoresController::class, 'criar']);
$rotas->put('/fornecedores/{id}', [\App\Controllers\FornecedoresController::class, 'atualizar']);
$rotas->patch('/fornecedores/{id}/ativo', [\App\Controllers\FornecedoresController::class, 'alternarAtivo']);

$rotas->get('/produtos', [\App\Controllers\ProdutosController::class, 'listar']);
$rotas->post('/produtos', [\App\Controllers\ProdutosController::class, 'criar']);
$rotas->put('/produtos/{id}', [\App\Controllers\ProdutosController::class, 'atualizar']);
$rotas->patch('/produtos/{id}/ativo', [\App\Controllers\ProdutosController::class, 'alternarAtivo']);

$rotas->get('/clientes', [\App\Controllers\ClientesController::class, 'listar']);
$rotas->post('/clientes', [\App\Controllers\ClientesController::class, 'criar']);
$rotas->put('/clientes/{id}', [\App\Controllers\ClientesController::class, 'atualizar']);

$caminho = $_SERVER['PATH_INFO'] ?? '/';

try {
    $rotas->despachar($_SERVER['REQUEST_METHOD'], $caminho);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::erro('Erro interno no servidor.', 500);
}
