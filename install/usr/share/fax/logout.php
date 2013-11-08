<?php
session_start();
if (isset($_SESSION['valid_user'])) $old_user = $_SESSION['valid_user'];
unset($_SESSION['valid_user']);
session_destroy();
?>
<html>
<head>
        <link rel="stylesheet" type="text/css" href="./css/default.css">
        <title>Envio de FAX</title>
</head>
<body><h1>Envio de FAX</h1>
<h2>Salida del sistema</h2>
<?php
echo "Vd. ha salido del sistema.<br>";
echo "<a href=\"index.php\">Volver a la pagina principal</a><br>";
?>
</body>
</html>
