<?php
  session_start();
  if (isset($_REQUEST['userid']) && isset($_REQUEST['password'])) { //el usuario ha intentado entrar con login y password
	// variables de autenticacion y LDAP
        $ldap['user']              = $_REQUEST['userid'];
        $ldap['pass']              = $_REQUEST['password'];
        //$ldap['host']              = '10.10.0.250'; // nombre del host o servidor
        $ldap['host']              = '172.16.0.85'; // nombre del host o servidor
        $ldap['port']              = 389; // puerto del LDAP en el servidor
        $ldap['dn']                = 'uid='.$ldap['user'].',ou=usuarios,dc=centro,dc=com'; // modificar respecto a los valores del LDAP
        $ldap['base']              = 'ou=grupos,dc=centro,dc=com';
        $ldap['filter']            = "(cn=PROFESORES)";
        $ldap['searchdn']          = 'uid=joindomain,ou=usuarios,dc=centro,dc=com';
        $ldap['searchpass']          = 'joindomain';
        // conexion a ldap
        $ldap['conn'] = ldap_connect( $ldap['host'], $ldap['port'] );
        ldap_set_option($ldap['conn'], LDAP_OPT_PROTOCOL_VERSION, 3);
        $is_valid_user = false;
	// match de usuario y password
        $ldap['bind'] = ldap_bind( $ldap['conn'], $ldap['dn'], $ldap['pass'] );
        if ($ldap['bind']){
                if ( ldap_bind( $ldap['conn'], $ldap['searchdn'], $ldap['searchpass']) ) {
                        // pertenencia a grupo
                        if ($result = ldap_search($ldap['conn'], $ldap['base'], $ldap['filter'], array("memberUid"))) {
                                $entries = ldap_get_entries($ldap['conn'], $result);
                                for ($i=0; $i<$entries[0]["memberuid"]['count'];$i++) {
                                        $uid = $entries[0]["memberuid"][$i];
                                        if ( $uid == $ldap['user'] ) {
                                                $is_valid_user = true;
                                                break ;
                                        }
                                }
                        }
                }
                ldap_unbind($ldap['conn']);
        }
	if ( $is_valid_user ) {
		$_SESSION['valid_user']=$_REQUEST['userid'];
	}
  }
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="./css/default.css">
        <title>Envio de FAX</title>
</head>
<body><h1>Envio de FAX</h1>
<?php
  if (isset($_SESSION['valid_user'])) {
    echo "Bienvenido, " . $_SESSION['valid_user'] . "<br>";
    echo "<a href=\"privado.php\">Sección privada</a><br>";
    echo "<a href=\"logout.php\">Salir del sistema</a><br>";
  }
  else //no esta registrado
    if (isset($_REQUEST['userid'])) { //Ha intentado entrar pero no ha podido;
      echo "ERROR: nombre de usuario o/y contraseña incorrecta/os<br>";
      echo "<a href=\"index.php\">Volver a la pagina principal</a><br>";
    }
    else { //No ha intentado entrar o acaba de salir
      echo "<h2>Acceso al sistema</h2>";
      echo "<form method=post action=\"index.php\">";
      echo "<p><label for='userid'>Usuario: </label><br>";
      echo "<input type=text name=userid></p>";
      echo "<p><label for='passsword'>Password: </label><br>";
      echo "<input type=password name=password></p>";
      echo "<input type=submit value=\"Entrar\">";
      echo "</form>";
    }
?>
</body>
</html>
