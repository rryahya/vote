<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); }
elseif ($_SESSION['role']==='admin') { header('Location: admin-users.php'); }
else { header('Location: vote.php'); }
exit;
