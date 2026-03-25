<?php

// ============================================================
//  DR Medical Center — Logout
//  Encerra a sessão e volta para a tela de login
// ============================================================

session_start();

// Destrói todos os dados da sessão
session_destroy();

// Redireciona para a página de login
header("Location: index.html");
exit;
