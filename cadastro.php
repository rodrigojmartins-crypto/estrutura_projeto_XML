<?php
// Alteração: usa as funções seguras de leitura, gravação e escape.
require_once __DIR__ . '/funcoes.php';

// Alteração: mantém os dados preenchidos e apresenta erros de validação.
$nome = '';
$celular = '';
$email = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: normaliza e valida todos os campos antes de gravá-los.
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $celular = trim((string) ($_POST['celular'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($nome === '' || $celular === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Preencha nome, celular e um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve possuir pelo menos 6 caracteres.';
    } else {
        try {
            $usuarios = carregarXml(ARQUIVO_USUARIOS, 'usuarios');
            // Alteração: impede o cadastro duplicado do mesmo e-mail.
            foreach ($usuarios->usuario as $usuario) {
                if (strcasecmp((string) $usuario->email, $email) === 0) {
                    $erro = 'Este e-mail já está cadastrado.';
                    break;
                }
            }
            if ($erro === '') {
                $novo = $usuarios->addChild('usuario');
                adicionarTextoXml($novo, 'nome', $nome);
                adicionarTextoXml($novo, 'celular', $celular);
                adicionarTextoXml($novo, 'email', $email);
                // Alteração: substitui MD5 pelo algoritmo seguro de hash de senha do PHP.
                adicionarTextoXml($novo, 'senha', password_hash($senha, PASSWORD_DEFAULT));
                salvarXml($usuarios, ARQUIVO_USUARIOS);
                // Alteração: corrige a codificação da mensagem exibida.
                echo '<!doctype html><html lang="pt-BR"><meta charset="UTF-8"><title>Cadastro</title>';
                echo '<p>Usuário cadastrado com sucesso! <a href="login.php">Fazer login</a></p>';
                exit;
            }
        } catch (RuntimeException $excecao) {
            $erro = $excecao->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Cadastro</title></head>
<body>
    <?php // Alteração: escapa mensagens e valores devolvidos ao formulário. ?>
    <?php if ($erro !== ''): ?><p><?= escapar($erro) ?></p><?php endif; ?>
    <form method="post">
        <label>Nome: <input type="text" name="nome" value="<?= escapar($nome) ?>" required></label><br>
        <label>Celular: <input type="text" name="celular" value="<?= escapar($celular) ?>" required></label><br>
        <label>E-mail: <input type="email" name="email" value="<?= escapar($email) ?>" required></label><br>
        <label>Senha: <input type="password" name="senha" minlength="6" required></label><br>
        <button type="submit">Cadastrar</button>
    </form>
    <p><a href="login.php">Já tenho cadastro</a></p>
</body>
</html>