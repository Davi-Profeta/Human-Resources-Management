<?php
session_start();

$nome  = htmlspecialchars($_SESSION['User'] ?? '');
$email = htmlspecialchars($_SESSION['Email'] ?? '');

echo <<<HTML
<div class="user-info">
    <div class="avatar">
        <i class="fa-regular fa-user"></i>
        <span class="status"></span>
    </div>
    <h3 id="userName">$nome</h3>
    <p id="userEmail">$email</p>
    <span class="badge">Online</span>
</div>

<div class="user-actions">
    <button id="logout">Sair</button>
    <button id="closeUser">Fechar</button>
</div>
HTML;