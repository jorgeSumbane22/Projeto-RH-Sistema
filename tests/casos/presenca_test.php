<?php
/** Teste de integração: lançamento manual de presença e contagem de faltas/horas extras. */

teste('Presenca::lancarManual grava e permite recontar faltas e horas extras do mês', function () {
    $db = Database::getConnection();

    $db->exec("INSERT INTO departamentos (nome) VALUES ('__TESTE_DEPTO2__')");
    $depId = (int) $db->lastInsertId();
    $db->exec("INSERT INTO cargos (titulo, departamento_id, salario_base) VALUES ('__TESTE_CARGO2__', $depId, 2000)");
    $cargoId = (int) $db->lastInsertId();
    $stmt = $db->prepare(
        "INSERT INTO funcionarios (nome, cpf, email, data_admissao, cargo_id, departamento_id, salario, status)
         VALUES ('__TESTE_FUNC2__', '000.TESTE.111-00', 'teste.presenca@__teste__.com', CURDATE(), :cargo, :dep, 2000, 'ativo')"
    );
    $stmt->execute([':cargo' => $cargoId, ':dep' => $depId]);
    $funcId = (int) $db->lastInsertId();

    try {
        $presencaModel = new Presenca();

        $presencaModel->lancarManual([
            'funcionario_id' => $funcId, 'data' => '2099-02-01', 'hora_entrada' => '08:00',
            'hora_saida' => '17:00', 'horas_extras' => 2, 'status' => 'presente', 'observacao' => null,
        ]);
        $presencaModel->lancarManual([
            'funcionario_id' => $funcId, 'data' => '2099-02-02', 'hora_entrada' => null,
            'hora_saida' => null, 'horas_extras' => 0, 'status' => 'falta', 'observacao' => 'Teste',
        ]);
        $presencaModel->lancarManual([
            'funcionario_id' => $funcId, 'data' => '2099-02-03', 'hora_entrada' => null,
            'hora_saida' => null, 'horas_extras' => 0, 'status' => 'falta', 'observacao' => 'Teste',
        ]);

        afirmarIgual(2, $presencaModel->contarFaltasMes($funcId, '2099-02'));
        afirmarProximo(2.0, $presencaModel->somarHorasExtrasMes($funcId, '2099-02'));

        // Testa o UPDATE via ON DUPLICATE KEY (mesma data, dados diferentes)
        $presencaModel->lancarManual([
            'funcionario_id' => $funcId, 'data' => '2099-02-01', 'hora_entrada' => '08:00',
            'hora_saida' => '19:00', 'horas_extras' => 4, 'status' => 'presente', 'observacao' => 'Atualizado',
        ]);
        afirmarProximo(4.0, $presencaModel->somarHorasExtrasMes($funcId, '2099-02'), 0.01, 'O relançamento deveria ter atualizado (não duplicado) o registro.');
    } finally {
        $db->exec("DELETE FROM presencas WHERE funcionario_id = $funcId");
        $db->exec("DELETE FROM funcionarios WHERE id = $funcId");
        $db->exec("DELETE FROM cargos WHERE id = $cargoId");
        $db->exec("DELETE FROM departamentos WHERE id = $depId");
    }
});
