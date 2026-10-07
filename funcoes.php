<?php

// Alteração: centraliza caminhos e corrige a leitura de arquivos XML vazios.
const ARQUIVO_USUARIOS = __DIR__ . DIRECTORY_SEPARATOR . 'usuarios.xml';
const ARQUIVO_TOPICOS = __DIR__ . DIRECTORY_SEPARATOR . 'topicos.xml';

function carregarXml(string $arquivo, string $raiz): SimpleXMLElement
{
    // Alteração: cria uma estrutura válida quando o arquivo ainda não tem conteúdo.
    $conteudo = is_file($arquivo) ? file_get_contents($arquivo) : false;
    if ($conteudo === false || trim($conteudo) === '') {
        return new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $raiz . '/>');
    }

    // Alteração: bloqueia acesso de rede pelo parser e detecta XML corrompido.
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($conteudo, SimpleXMLElement::class, LIBXML_NONET);
    libxml_clear_errors();
    if ($xml === false || $xml->getName() !== $raiz) {
        throw new RuntimeException('O arquivo de dados está corrompido ou possui uma estrutura inválida.');
    }
    return $xml;
}

function salvarXml(SimpleXMLElement $xml, string $arquivo): void
{
    // Alteração: grava com bloqueio e verifica falhas de escrita.
    $conteudo = $xml->asXML();
    if ($conteudo === false || file_put_contents($arquivo, $conteudo, LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível salvar os dados.');
    }
}

function adicionarTextoXml(SimpleXMLElement $pai, string $nome, string $valor): SimpleXMLElement
{
    // Alteração: adiciona caracteres especiais sem interpretá-los como entidades XML.
    $filho = $pai->addChild($nome);
    $filho[0] = $valor;
    return $filho;
}

function escapar(mixed $valor): string
{
    // Alteração: impede injeção de HTML ao exibir dados salvos.
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function obterIndice(mixed $valor): ?int
{
    // Alteração: valida índices antes de acessar tópicos ou comentários.
    if (!is_string($valor) && !is_int($valor)) {
        return null;
    }
    $indice = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return $indice === false ? null : $indice;
}

function tokenCsrf(): string
{
    // Alteração: gera proteção para formulários que modificam dados.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(mixed $token): bool
{
    // Alteração: confere se o formulário foi emitido pela sessão atual.
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function encerrarComErro(string $mensagem, int $status = 400): never
{
    // Alteração: apresenta erros com status HTTP e saída segura.
    http_response_code($status);
    echo '<!doctype html><html lang="pt-BR"><meta charset="UTF-8"><title>Erro</title>';
    echo '<p>' . escapar($mensagem) . '</p><p><a href="listar.php">Voltar aos tópicos</a></p>';
    exit;
}