<?php
/**
 * Teste de integração: gera a folha de um funcionário fictício e confere o
 * cálculo. Cria seus próprios dados (departamento/cargo/funcionário de teste)
 * e remove tudo ao final, não deixando resíduo no banco.
 */

teste('FolhaPagamento::gerarFolhaMensal calcula o salário líquido corretamente', function () {
    $db = Database::getConnection();

    // --- Fixture: cria departamento, cargo e funcionário isolados para o teste ---
    $db->exec("INSERT INTO departamentos (nome) VALUES ('__TESTE_DEPTO__')");
    $depId = (int) $db->lastInsertId();

    $db->exec("INSERT INTO cargos (titulo, departamento_id, salario_base) VALUES ('__TESTE_CARGO__', $depId, 3000)");
    $cargoId = (int) $db->lastInsertId();

    $stmt = $db->prepare(
        "INSERT INTO funcionarios (nome, cpf, email, data_admissao, cargo_id, departamento_id, salario, status)
         VALUES ('__TESTE_FUNC__', '000.TESTE.000-00', 'teste.folha@__teste__.com', CURDATE(), :cargo, :dep, 3000, 'ativo')"
    );
    $stmt->execute([':cargo' => $cargoId, ':dep' => $depId]);
    $funcId = (int) $db->lastInsertId();

    try {
        $mesReferencia = '2099-01'; // mês fictício, não colide com dados reais
        $folhaModel = new FolhaPagamento();
        $folhaModel->gerarFolhaMensal($mesReferencia);

        $folha = $folhaModel->listar($mesReferencia);
        $registroTeste = null;
        foreach ($folha as $f) {
            if ((int) $f['funcionario_id'] === $funcId) {
                $registroTeste = $f;
            }
        }

        afirmarVerdadeiro($registroTeste !== null, 'A folha do funcionário de teste deveria ter sido gerada.');
        afirmarProximo(3000.0, (float) $registroTeste['salario_base']);
        afirmarProximo(0.0, (float) $registroTeste['horas_extras_valor'], 0.01, 'Sem ponto lançado, não deveria haver horas extras.');
        afirmarProximo(0.0, (float) $registroTeste['faltas_desconto'], 0.01, 'Sem ponto lançado, não deveria haver desconto de faltas.');

        // líquido = base + bônus(0) + extras(0) - descontos(tabela progressiva) - faltas(0)
        $descontoEsperado = calcularDescontoProgressivo(3000.0);
        afirmarProximo(3000.0 - $descontoEsperado, (float) $registroTeste['salario_liquido']);
    } finally {
        // --- Limpeza: remove tudo que o teste criou, nesta ordem (FKs) ---
        $db->exec("DELETE FROM folha_pagamento WHERE funcionario_id = $funcId");
        $db->exec("DELETE FROM funcionarios WHERE id = $funcId");
        $db->exec("DELETE FROM cargos WHERE id = $cargoId");
        $db->exec("DELETE FROM departamentos WHERE id = $depId");
    }
});
