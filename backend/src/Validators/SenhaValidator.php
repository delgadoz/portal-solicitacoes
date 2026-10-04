<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Valida os dados da troca de senha e a política de senha forte.
 */
final class SenhaValidator
{
    public const TAMANHO_MIN = 8;

    /**
     * O bcrypt (password_hash) só considera os primeiros 72 bytes da senha; o resto seria ignorado
     * em silêncio. Limitar o tamanho evita que o usuário acredite ter uma senha que não tem.
     */
    public const TAMANHO_MAX_BYTES = 72;

    /**
     * @return array{senha_atual: string, nova_senha: string}
     */
    public function validarTroca(array $dados): array
    {
        $erros = [];

        $senhaAtual = is_string($dados['senha_atual'] ?? null) ? $dados['senha_atual'] : '';
        $novaSenha = is_string($dados['nova_senha'] ?? null) ? $dados['nova_senha'] : '';
        $confirmacao = is_string($dados['confirmacao'] ?? null) ? $dados['confirmacao'] : '';

        if ($senhaAtual === '') {
            $erros['senha_atual'] = 'Informe a senha atual.';
        }

        $erroPolitica = $this->verificarPolitica($novaSenha);
        if ($erroPolitica !== null) {
            $erros['nova_senha'] = $erroPolitica;
        } elseif ($senhaAtual !== '' && hash_equals($senhaAtual, $novaSenha)) {
            $erros['nova_senha'] = 'A nova senha deve ser diferente da atual.';
        }

        if ($confirmacao === '') {
            $erros['confirmacao'] = 'Confirme a nova senha.';
        } elseif (!hash_equals($novaSenha, $confirmacao)) {
            $erros['confirmacao'] = 'A confirmação não confere com a nova senha.';
        }

        if ($erros !== []) {
            throw new ValidationException($erros);
        }

        return ['senha_atual' => $senhaAtual, 'nova_senha' => $novaSenha];
    }

    /**
     * Política: mínimo de 8 caracteres, com pelo menos 1 letra maiúscula, 1 número e 1 caractere especial.
     * Retorna a mensagem de erro, ou null se a senha atende à política.
     */
    public function verificarPolitica(string $senha): ?string
    {
        if ($senha === '') {
            return 'Informe a nova senha.';
        }

        $faltando = [];
        if (mb_strlen($senha) < self::TAMANHO_MIN) {
            $faltando[] = 'pelo menos ' . self::TAMANHO_MIN . ' caracteres';
        }
        if (!preg_match('/\p{Lu}/u', $senha)) {
            $faltando[] = '1 letra maiúscula';
        }
        if (!preg_match('/\d/', $senha)) {
            $faltando[] = '1 número';
        }
        // Especial: qualquer caractere que não seja letra, número ou espaço (ex.: ! @ # $ % & * . -)
        if (!preg_match('/[^\p{L}\p{N}\s]/u', $senha)) {
            $faltando[] = '1 caractere especial';
        }

        if ($faltando !== []) {
            return 'A senha precisa ter ' . $this->juntar($faltando) . '.';
        }

        if (strlen($senha) > self::TAMANHO_MAX_BYTES) {
            return 'A senha é longa demais (máximo de ' . self::TAMANHO_MAX_BYTES . ' bytes).';
        }

        return null;
    }

    /** ["a", "b", "c"] → "a, b e c" */
    private function juntar(array $itens): string
    {
        $ultimo = array_pop($itens);

        return $itens === [] ? $ultimo : implode(', ', $itens) . ' e ' . $ultimo;
    }
}
