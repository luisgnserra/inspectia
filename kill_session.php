<?php
session_start();          // Garante que a sessão está ativa
session_unset();          // Limpa as variáveis da sessão
session_destroy();        // Encerra a sessão

// Opcional: Redireciona para a página de login ou inicial
header("Location: index.php");
exit;
