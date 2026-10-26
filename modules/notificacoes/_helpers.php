<?php
/**
 * _helpers.php - Funções auxiliares para notificações
 */

if (!function_exists('notificacaoIcon')) {
    function notificacaoIcon(string $tipo): string
    {
        $icons = [
            'sistema' => 'bi-gear',
            'rh' => 'bi-people',
            'folha' => 'bi-cash-stack',
            'ferias' => 'bi-sun',
            'presenca' => 'bi-clock',
            'avaliacao' => 'bi-star',
            'recrutamento' => 'bi-briefcase',
            'documento' => 'bi-file-earmark',
            'usuario' => 'bi-person',
            'seguranca' => 'bi-shield-check',
        ];
        return $icons[$tipo] ?? 'bi-bell';
    }
}

if (!function_exists('notificacaoBadge')) {
    function notificacaoBadge(string $tipo): string
    {
        $badges = [
            'sistema' => 'secondary',
            'rh' => 'primary',
            'folha' => 'success',
            'ferias' => 'warning',
            'presenca' => 'info',
            'avaliacao' => 'purple',
            'recrutamento' => 'primary',
            'documento' => 'info',
            'usuario' => 'secondary',
            'seguranca' => 'danger',
        ];
        return $badges[$tipo] ?? 'secondary';
    }
}