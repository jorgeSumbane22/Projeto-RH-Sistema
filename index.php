<?php
/**
 * index.php (raiz)
 * Ponto de entrada do sistema: redireciona para o dashboard (se logado)
 * ou para a tela de login.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (estaLogado()) {
    redirecionar('/modules/dashboard/index.php');
} else {
    redirecionar('/modules/auth/login.php');
}
