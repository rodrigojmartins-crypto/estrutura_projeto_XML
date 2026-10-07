<?php
// Alteração: inicializa a sessão e carrega as rotinas comuns.
session_start();
require_once __DIR__ . '/funcoes.php';

$email = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: valida a entrada e trata arquivo XML vazio ou inválido.
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    try {
        $usuarios = carregarXml(ARQUIVO_USUARIOS, 'usuarios');
        foreach ($usuarios->usuario as $usuario) {
            $hash = (string) $usuario->senha;
            // Alteração: aceita registros antigos em MD5 e os atualiza após o login.
            $senhaCorreta = password_verify($senha, $hash)
                || (preg_match('/^[a-f0-9]{32}$/i', $hash) === 1 && hash_equals(strtolower($hash), md5($senha)));
            if (strcasecmp((string) $usuario->email, $email) === 0 && $senhaCorreta) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = (string) $usuario->email;
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
                    salvarXml($usuarios, ARQUIVO_USUARIOS);
                }
                // Alteração: redireciona após o POST para evitar reenvio do formulário.
                header('Location: listar.php');
                exit;
            }
        }
        $erro = 'Login inválido.';
    } catch (RuntimeException $excecao) {
        $erro = $excecao->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Login</title></head>
<body>
    <?php // Alteração: mostra o erro com segurança e corrige os textos corrompidos. ?>
    <?php if ($erro !== ''): ?><p><?= escapar($erro) ?></p><?php endif; ?>
    <form method="post">
        <label>E-mail: <input type="email" name="email" value="<?= escapar($email) ?>" required></label><br>
        <label>Senha: <input type="password" name="senha" required></label><br>
        <button type="submit">Entrar</button>
    </form>
    <p><a href="cadastro.php">Criar cadastro</a> | <a href="listar.php">Ver tópicos</a></p>
</body>
</html>