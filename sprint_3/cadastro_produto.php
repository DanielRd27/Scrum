<?php
// Inclui o arquivo que valida a sessão do usuário
include('valida_sessao.php');
// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');

// Função para redimensionar e salvar a imagem
function redimensionarESalvarImagem($arquivo, $largura = 80, $altura = 80) {
    $diretorio_destino = "img/";
    if (!file_exists($diretorio_destino)) {
        mkdir($diretorio_destino, 0777, true);
    }
    $nome_arquivo = uniqid() . '_' . basename($arquivo["name"]);
    $caminho_completo = $diretorio_destino . $nome_arquivo;
    $tipo_arquivo = strtolower(pathinfo($caminho_completo, PATHINFO_EXTENSION));

    // Verifica se é uma imagem válida
    $check = getimagesize($arquivo["tmp_name"]);
    if($check === false) {
        return "O arquivo não é uma imagem válida.";
    }

    // Verifica o tamanho do arquivo (limite de 5MB)
    if ($arquivo["size"] > 5000000) {
        return "O arquivo é muito grande. O tamanho máximo permitido é 5MB.";
    }

    // Permite apenas alguns formatos de arquivo
    if($tipo_arquivo != "jpg" && $tipo_arquivo != "png" && $tipo_arquivo != "jpeg" && $tipo_arquivo != "gif" ) {
        return "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
    }

    // Cria uma nova imagem a partir do arquivo enviado
    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") {
        $imagem_original = imagecreatefromjpeg($arquivo["tmp_name"]);
    } elseif ($tipo_arquivo == "png") {
        $imagem_original = imagecreatefrompng($arquivo["tmp_name"]);
    } elseif ($tipo_arquivo == "gif") {
        $imagem_original = imagecreatefromgif($arquivo["tmp_name"]);
    }

    // Obtém as dimensões originais da imagem
    $largura_original = imagesx($imagem_original);
    $altura_original = imagesy($imagem_original);

    // Calcula as novas dimensões mantendo a proporção
    $ratio = min($largura / $largura_original, $altura / $altura_original);
    $nova_largura = $largura_original * $ratio;
    $nova_altura = $altura_original * $ratio;

    // Cria uma nova imagem com as dimensões calculadas
    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

    // Redimensiona a imagem original para a nova imagem
    imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

    // Salva a nova imagem
    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") {
        imagejpeg($nova_imagem, $caminho_completo, 90);
    } elseif ($tipo_arquivo == "png") {
        imagepng($nova_imagem, $caminho_completo);
    } elseif ($tipo_arquivo == "gif") {
        imagegif($nova_imagem, $caminho_completo);
    }

    // Libera a memória
    imagedestroy($imagem_original);
    imagedestroy($nova_imagem);

    return $caminho_completo;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $fornecedor_id = $_POST['fornecedor_id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $estado = $_POST['estado'];
    $quantidade = $_POST['quantidade'];
    $preco = str_replace(',', '.', $_POST['preco']); // Converte vírgula para ponto

    // Processa o upload da imagem
    $imagem = "";
    if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $resultado_upload = redimensionarESalvarImagem($_FILES['imagem']);
        if(strpos($resultado_upload, 'img/') === 0) {
            $imagem = $resultado_upload;
        } else {
            $mensagem_erro = $resultado_upload;
        }
    }
    
    // Prepara a query SQL para inserção ou atualização
    if ($id) {
        // Se o ID existe, é uma atualização
        $sql = "UPDATE produtos SET fornecedor_id=?, nome=?, descricao=?, preco=?";
        $params = [$fornecedor_id, $nome, $descricao, $preco];
        if($imagem) {
            $sql .= ", imagem=?";
            $params[] = $imagem;
        }
        $sql .= " WHERE id=?";
        $params[] = $id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $mensagem = "Produto atualizado com sucesso!";
    } else {
        // Se não há ID, é uma nova inserção
        $sql = "INSERT INTO produtos (fornecedor_id, nome, descricao, preco, imagem, estado, quantidade) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $fornecedor_id, $nome, $descricao, $preco, $imagem, $estado, $quantidade);
        $mensagem = "Produto cadastrado com sucesso!";
    }

    // Executa a query e verifica se houve erro
    if ($stmt->execute()) {
        $class = "success";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $class = "error";
    }
}

// Verifica se foi solicitada a exclusão de um produto
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM produtos WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $mensagem = "Produto excluído com sucesso!";
        $class = "success";
    } else {
        $mensagem = "Erro ao excluir produto: " . $stmt->error;
        $class = "error";
    }
}

// Busca todos os produtos para listar na tabela
$produtos = $conn->query("SELECT p.id, p.nome, p.descricao, p.estado, p.quantidade, p.preco, p.imagem, f.nome AS fornecedor_nome FROM produtos p JOIN fornecedores f ON p.fornecedor_id = f.id");

// Se foi solicitada a edição de um produto, busca os dados dele
$produto = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();
    $stmt->close();
}

// Busca todos os fornecedores para o select do formulário
$fornecedores = $conn->query("SELECT id, nome FROM fornecedores");
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
        <h2>Cadastro de Produto</h2>
        <?php if (isset($mensagem)): ?>
            <p class="message <?php echo $class; ?>"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <!-- Formulário para cadastro/edição de produto -->
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $produto['id'] ?? ''; ?>">
            
            <label for="fornecedor_id">Fornecedor:</label>
            <select name="fornecedor_id" required>
                <?php while ($row = $fornecedores->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php if ($produto && $produto['fornecedor_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['nome']; ?></option>
                <?php endwhile; ?>
            </select>
            
            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo $produto['nome'] ?? ''; ?>" required>
            
            <label for="descricao">Descrição:</label>
            <textarea name="descricao"><?php echo $produto['descricao'] ?? ''; ?></textarea>
            
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="antiga"><?php echo $produto['estado'] ?? ''; ?></option>
                <option value="Usado">Usado</option>
                <option value="Semi-novo">Pequenas avarias</option>
                <option value="Novo">Novo</option>
            </select>

            <label for="quantidade">Quantidade em estoque:</label>
            <input type="number" name="quantidade" value="<?php echo $produto['quantidade'] ?? ''; ?>" required>

            <label for="preco">Preço:</label>
            <input type="text" name="preco" value="<?php echo $produto['preco'] ?? ''; ?>" required>
            
            <label for="imagem">Imagem:</label>
            <input type="file" name="imagem" accept="image/*">
            <?php if (isset($produto['imagem']) && $produto['imagem']): ?>
                <img src="<?php echo $produto['imagem']; ?>" alt="Imagem atual do produto" class="update-image">
            <?php endif; ?>
            <br>
            <button type="submit"><?php echo $produto ? 'Atualizar' : 'Cadastrar'; ?></button>
        </form>
        
        <!-- Exibe mensagens de sucesso ou erro -->
        

        <a href="listagem_produtos.php" class="back-button">Voltar</a>
    </div>
</body>
</html>