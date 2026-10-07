<?php
// Alteração: usa caminhos absolutos e as rotinas comuns de XML.
session_start();
require_once __DIR__ . '/funcoes.php';

if (!isset($_SESSION['usuario'])) {
    encerrarComErro('Você precisa estar logado para criar um tópico.', 403);
}

$titulo = '';
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: valida o token e os campos antes de acessar o XML.
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        encerrarComErro('Formulário expirado. Tente novamente.', 403);
    }
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
    if ($titulo === '' || $mensagem === '') {
        $erro = 'Informe o título e a mensagem.';
    } else {
        try {
            $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
            $novo = $topicos->addChild('topico');
            adicionarTextoXml($novo, 'autor', (string) $_SESSION['usuario']);
            adicionarTextoXml($novo, 'titulo', $titulo);
            adicionarTextoXml($novo, 'mensagem', $mensagem);
            $novo->addChild('comentarios');
            salvarXml($topicos, ARQUIVO_TOPICOS);
            // Alteração: evita a criação duplicada ao atualizar a página.
            $_SESSION['mensagem_sucesso'] = 'Tópico criado com sucesso!';
            header('Location: listar.php');
            exit;
        } catch (RuntimeException $excecao) {
            $erro = $excecao->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Criar tópico</title></head>
<body>
    <?php // Alteração: inclui proteção CSRF e escapa os valores exibidos. ?>
    <?php if ($erro !== ''): ?><p><?= escapar($erro) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <label>Título: <input type="text" name="titulo" value="<?= escapar($titulo) ?>" required></label><br>
        <label>Mensagem:<br><textarea name="mensagem" required><?= escapar($mensagem) ?></textarea></label><br>
        <button type="submit">Criar tópico</button>
    </form>
    <p><a href="listar.php">Voltar aos tópicos</a></p>
</body>
</html>