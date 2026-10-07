<?php
// Nova página: prepara a sessão, as funções auxiliares e os dados da listagem.
session_start();
require_once __DIR__ . '/funcoes.php';

$erro = '';
$mensagemSucesso = (string) ($_SESSION['mensagem_sucesso'] ?? '');
unset($_SESSION['mensagem_sucesso']);

try {
    $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
} catch (RuntimeException $excecao) {
    $erro = $excecao->getMessage();
    $topicos = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><topicos/>');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fórum</title>
</head>
<body>
    <header>
        <h1>Tópicos do fórum</h1>
        <nav>
            <?php // Nova página: mostra as ações adequadas ao estado de autenticação. ?>
            <?php if (isset($_SESSION['usuario'])): ?>
                <span>Conectado como <?= escapar($_SESSION['usuario']) ?></span>
                | <a href="criar_topico.php">Criar tópico</a>
            <?php else: ?>
                <a href="login.php">Entrar</a> | <a href="cadastro.php">Cadastrar</a>
            <?php endif; ?>+
        </nav>
    </header>

    <?php if ($mensagemSucesso !== ''): ?><p><?= escapar($mensagemSucesso) ?></p><?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p><?= escapar($erro) ?></p>
    <?php elseif (count($topicos->topico) === 0): ?>
        <p>Nenhum tópico foi criado ainda.</p>
    <?php endif; ?>

    <?php // Nova página: lista tópicos e comentários sempre escapando o XML. ?>
    <?php // Alteração: usa contador porque o SimpleXML devolve o nome da tag como chave. ?>
    <?php $id = 0; ?>
    <?php foreach ($topicos->topico as $topico): ?>
        <article>
            <h2><?= escapar($topico->titulo) ?></h2>
            <p><?= nl2br(escapar($topico->mensagem)) ?></p>
            <p><small>Autor: <?= escapar($topico->autor) ?></small></p>

            <h3>Comentários</h3>
            <?php if (count($topico->comentarios->comentario) === 0): ?>
                <p>Este tópico ainda não possui comentários.</p>
            <?php endif; ?>

            <?php $comentarioId = 0; ?>
            <?php foreach ($topico->comentarios->comentario as $comentario): ?>
                <section>
                    <p><strong><?= escapar($comentario->nome) ?>:</strong> <?= nl2br(escapar($comentario->mensagem)) ?></p>
                    <?php // Nova página: o autor recebe um formulário POST seguro para exclusão. ?>
                    <?php if (isset($_SESSION['usuario']) && (string) $_SESSION['usuario'] === (string) $topico->autor): ?>
                        <form method="post" action="excluir.php">
                            <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                            <input type="hidden" name="id" value="<?= escapar($id) ?>">
                            <input type="hidden" name="comentario" value="<?= escapar($comentarioId) ?>">
                            <button type="submit">Excluir comentário</button>
                        </form>
                    <?php endif; ?>
                </section>
                <?php // Alteração: avança o índice para o próximo comentário. ?>
                <?php $comentarioId++; ?>
            <?php endforeach; ?>

            <?php // Nova página: disponibiliza o formulário de comentário com proteção CSRF. ?>
            <form method="post" action="comentar.php">
                <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                <input type="hidden" name="id" value="<?= escapar($id) ?>">
                <label>Nome: <input type="text" name="nome" required></label><br>
                <label>Comentário:<br><textarea name="mensagem" required></textarea></label><br>
                <button type="submit">Comentar</button>
            </form>
        </article>
        <hr>
        <?php // Alteração: avança o índice usado pelo próximo tópico. ?>
        <?php $id++; ?>
    <?php endforeach; ?>
</body>
</html>