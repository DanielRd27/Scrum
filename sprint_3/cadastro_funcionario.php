<?php
// Inclui o arquivo que valida a sessão do usuário
include('valida_sessao.php');
// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');


// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = MD5($_POST['senha']);

    // Prepara a query SQL para inserção ou atualização
    if ($id) {
        // Se o ID existe, é uma atualização
        $sql = "UPDATE fornecedores SET nome='$nome', email='$email', senha='$senha'";
        $sql .= " WHERE id='$id'";
        $mensagem = "Funcionário atualizado com sucesso!";
    } else {
        // Se não há ID, é uma nova inserção
        $sql = "INSERT INTO funcionarios (nome, email, senha) VALUES ('$nome', '$email', '$senha')";
        $mensagem = "Funcionário cadastrado com sucesso!";
    }

    // Executa a query e verifica se houve erro
    if ($conn->query($sql) !== TRUE) {
        $mensagem = "Erro: " . $conn->error;
    }
}

// Verifica se foi solicitada a exclusão de um fornecedor
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM funcionarios WHERE id='$delete_id'";
    if ($conn->query($sql) === TRUE) {
        $mensagem = "Funcionárior excluído com sucesso!";
    } else {
        $mensagem = "Erro ao excluir funcionário: " . $conn->error;
    }
}

// Busca todos os fornecedores para listar na tabela
$funcionarios = $conn->query("SELECT * FROM funcionarios");

// Se foi solicitada a edição de um fornecedor, busca os dados dele
$funcionario = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $funcionario = $conn->query("SELECT * FROM funcionarios WHERE id='$edit_id'")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Principal</title>
    <!-- Link para o arquivo CSS para estilização da página -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include('header.html'); ?>
    <div class="container">
        <h2>Cadastro de Funcionários</h2>

        <?php
        if (isset($mensagem)) echo "<p class='message " . (strpos($mensagem, 'Erro') !== false ? "error" : "success") . "'>$mensagem</p>";
        if (isset($mensagem_erro)) echo "<p class='message error'>$mensagem_erro</p>";
        ?>
        <!-- Formulário para cadastro/edição de fornecedor -->
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $funcionario['id'] ?? ''; ?>">
            
            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo $funcionarios['nome'] ?? ''; ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo $funcionario['email'] ?? ''; ?>">
            
            <label for="senha">Senha:</label>
            <input type="text" name="senha" value="<?php echo $funcionario['telefone'] ?? ''; ?>">
            <br>
            <button type="submit"><?php echo $funcionario ? 'Atualizar' : 'Cadastrar'; ?></button>
        </form>
        
        <!-- Exibe mensagens de sucesso ou erro -->
    

        <div class="botoes">
          <a href="listagem_funcionarios.php" class="back-button">Voltar</a>
        </div>
    </div>
</body>
</html>