<?php

use App\Middleware\Cors;
use App\Router;
use App\Support\ErroDeNegocio;
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

// "Hoje" do sistema é o de Brasília, não o do servidor (Cloud Run roda
// em UTC) — vencimento de parcela e data de venda dependem disso.
date_default_timezone_set('America/Sao_Paulo');

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

$rotas->get('/entradas', [\App\Controllers\EntradasController::class, 'listar']);
$rotas->get('/entradas/{id}', [\App\Controllers\EntradasController::class, 'detalhar']);
$rotas->post('/entradas', [\App\Controllers\EntradasController::class, 'criar']);

$rotas->get('/vendas', [\App\Controllers\VendasController::class, 'listar']);
$rotas->get('/vendas/{id}', [\App\Controllers\VendasController::class, 'detalhar']);
$rotas->post('/vendas', [\App\Controllers\VendasController::class, 'criar']);
$rotas->patch('/vendas/{id}/cancelar', [\App\Controllers\VendasController::class, 'cancelar']);

$rotas->get('/contas-a-receber', [\App\Controllers\ContasReceberController::class, 'listar']);
$rotas->patch('/contas-a-receber/{id}/pagar', [\App\Controllers\ContasReceberController::class, 'pagar']);

$rotas->get('/condicionais', [\App\Controllers\CondicionaisController::class, 'listar']);
$rotas->get('/condicionais/{id}', [\App\Controllers\CondicionaisController::class, 'detalhar']);
$rotas->post('/condicionais', [\App\Controllers\CondicionaisController::class, 'criar']);
$rotas->post('/condicionais/{id}/finalizar', [\App\Controllers\CondicionaisController::class, 'finalizar']);
$rotas->patch('/condicionais/{id}/devolver', [\App\Controllers\CondicionaisController::class, 'devolver']);

$rotas->get('/inventario/ajustes', [\App\Controllers\InventarioController::class, 'ajustes']);
$rotas->post('/inventario', [\App\Controllers\InventarioController::class, 'finalizar']);
$rotas->post('/inventario/perdas', [\App\Controllers\InventarioController::class, 'registrarPerda']);

$rotas->get('/custos-fixos', [\App\Controllers\CustosFixosController::class, 'listar']);
$rotas->post('/custos-fixos', [\App\Controllers\CustosFixosController::class, 'criar']);
$rotas->put('/custos-fixos/{id}', [\App\Controllers\CustosFixosController::class, 'atualizar']);
$rotas->patch('/custos-fixos/{id}/ativo', [\App\Controllers\CustosFixosController::class, 'alternarAtivo']);

$rotas->get('/dashboard', [\App\Controllers\RelatoriosController::class, 'dashboard']);
$rotas->get('/relatorios', [\App\Controllers\RelatoriosController::class, 'gerar']);

$caminho = $_SERVER['PATH_INFO'] ?? '/';

try {
    $rotas->despachar($_SERVER['REQUEST_METHOD'], $caminho);
} catch (ErroDeNegocio $e) {
    Response::erro($e->getMessage(), $e->status());
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::erro('Erro interno no servidor.', 500);
}
