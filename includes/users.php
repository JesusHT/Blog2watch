<?php 

	require 'db.php';

	use PHPMailer\PHPMailer\PHPMailer;

	require 'phpmailer/PHPMailer/src/PHPMailer.php';
	require 'phpmailer/PHPMailer/src/SMTP.php';

	# Declaramos las variables que vamos a utilizar, esto con el fin de que no aparezca el warning de variable indefinida.
	$user = "";
	$pass = "";
	$question = "";
	$respuesta = "";
	$message = "";
	$correo = "";
	$id_user = "";
	$newPass = "";
	
	function validarUsuario($name, $conn){
		$records = $conn -> prepare('SELECT * FROM users WHERE name = :name');
		$records -> bindParam(':name', $name);
		$records -> execute();
		$results = $records -> fetch(PDO::FETCH_ASSOC);

		return $results;
	}

	# Registro de usuarios 	
	if (isset($_POST['name']) && isset($_POST['pregunta']) && isset($_POST['respuesta'])) {
		$user = $_POST['name'];
		$pass = $_POST['pass'];
		$question = $_POST['pregunta'];
		$respuesta = $_POST['respuesta'];

		if (!empty($user) && !empty($pass) && !empty($question) && !empty($respuesta)) {

			$validar = validarUsuario($user, $conn);

		    if (is_countable($validar) > 0) {
		    	$message = '<p class="bg-red fw-bold text-white p-1">El usuario ya existe</p>';
		    } else {
			    $sql = "INSERT INTO users (name, pass, pregunta, respuesta) VALUES (:name, :pass, :pregunta, :respuesta)";
			    $stmt = $conn->prepare($sql);

			    $stmt -> bindParam(':name', $user);
			    $password = password_hash($pass, PASSWORD_BCRYPT);

			    $stmt -> bindParam(':pass', $password);
			    $stmt -> bindParam(':pregunta', $question);
			    $stmt -> bindParam(':respuesta', $respuesta);

				$message = $stmt -> execute() ? '<p class="bg-green fw-bold text-white p-1">¡Usuario registrado exitosamente!</p>' : '<p class="bg-red fw-bold text-white p-1">¡No se ha podido registrar el usuario!</p>';
			} 
		}

		
	# Inicio de sesión  
	} else if (isset($_POST['name']) && isset($_POST['pass']) && empty($_POST['user2'])) {
		session_start(); 

		$user = $_POST['name'];
		$pass = $_POST['pass'];

		if (!empty($user) && !empty($pass)) {
			$validar = validarUsuario($user, $conn);

		    if (is_countable($validar) > 0 && password_verify($pass, $validar['pass'])) {
		      	if ($validar['name'] == 'Administrador'){ 
		      		$_SESSION['user_id'] = $validar['id'];
		     		header("Location: index-administrador.php"); 
		      	} else { 
		      		$_SESSION['user_id'] = $validar['id']; 
		     		header("Location: index-user.php");
		      	}
		    } else {
				$message = "El usuario y/o la contraseña son incorrectos"; 
		    }
		}


	# Recuperación de contraseña
	} else if (isset($_POST['correo']) && isset($_POST['respuesta']) && isset($_POST['pregunta']) && isset($_POST['user2'])){
		$user = $_POST['user2'];
		$question = $_POST['pregunta'];
		$respuesta = $_POST['respuesta'];
		$correo = $_POST['correo'];

		if (!empty($correo) && !empty($user) && !empty($respuesta)) {
			$validar = validarUsuario($user, $conn);

		    if (is_countable($validar) > 0) {
			    if ($results['respuesta'] == $respuesta) {
			      	$sql = "UPDATE users SET pass = :pass WHERE id = :id";
			      	$stmt = $conn -> prepare($sql);

			      	$stmt -> bindParam(':id', $results['id']);

			      	$newpass = "";
					$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
					$max = strlen($pattern)-1;
					for($i = 0; $i < 10; $i++){ 
						$newpass .= substr($pattern, mt_rand(0,$max), 1);
					}

					$mail = new PHPMailer(true);

					//Server settings
					$mail->SMTPDebug = 2;                                 // Enable verbose debug output
					$mail->isSMTP();                                      // Set mailer to use SMTP
					$mail->Host = 'smtp.gmail.com';                   	  // Specify main and backup SMTP servers
					$mail->SMTPAuth = true;                               // Enable SMTP authentication
					$mail->Username = 'blogtwowatch@gmail.com';           // SMTP username
					$mail->Password = '!Root123';                         // SMTP password
					$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
					$mail->Port = 587;                                    // TCP port to connect to
		
					//Recipients
					$mail->setFrom('blogtwowatch@gmail.com', 'Blog2Watch');
					$mail->addAddress($correo, $user);
		
					//Content
					$mail->isHTML(true);                                  // Set email format to HTML
					$mail->Subject = 'Recuperar password';
					$mail->Body    = '<!DOCTYPE html>
					<html lang="es">
					<head>
						<meta charset="UTF-8">
						<meta http-equiv="X-UA-Compatible" content="IE=edge">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
					</head>
					<body style="background-color: #000000; color: #fff;">
						<div align="center"><img src="https://i.ibb.co/dMWmZGV/logo.png" alt="Blog2Watch" title="Blog2Watch" width="250"></div>
						<div style="height: 400px; padding: 10px 50px 20px;">
							<p>Nueva contraseña: ' . $newpass . '</p>
						</div>
					</body>
					</html>';
		
					$mail->send();

			      	$password = password_hash($newpass, PASSWORD_BCRYPT);
			      	$stmt -> bindParam(':pass', $password);

			      	if ($stmt -> execute()) {
						$_COOKIE['user'] = null;
					  	$message = '<p class="bg-green fw-bold text-white p-1">¡Se ha enviado a tu correo tu nueva contraseña!</p>';
					} else {
					 	$message = '<p class="bg-red fw-bold text-white p-1">¡No se ha podido restablecer la contraseña!</p>';
					}
			    } else {
			      	$message = '<p class="bg-red fw-bold text-white p-1">Datos incorrectos</p>';
			    }
		    } else {
		      	$message = '<p class="bg-red fw-bold text-white p-1">El usuario no existe. <a href="sign_up.php">Regístrate</a></p>';
		    }
		}
	}
	
	# Recuperación de contraseña (Validar si el usuario existe)
	if (isset($_POST['user'])){
		$user = $_POST['user'];
		
		if (!empty($user)) {
			$validar = validarUsuario($user, $conn);

			if (is_countable($validar) > 0) {
				setcookie("user", $user);
				header("Location: validacion.php");
			} else {
				$message = '<p class="bg-red fw-bold text-white p-1">El usuario no existe. <a href="sign_up.php">Regístrate</a></p>';
			}
		}
	}

	# Validamos que la COOKIE se haya creado correctamente para poder mostrar la pregunta de seguridad sin necesidad de que el usuario la ponga, esto por el posible escenario donde se le olvidé la pregunta.
	if (isset($_COOKIE['user'])) {  
		$validar = validarUsuario($_COOKIE['user'], $conn);

		$preguntas = ['¿Cómo rayos lograste ver esta pregunta?','¿Cuál es el nombre de mi mascota?','¿Cuál es mi canción favorita?','¿Cuál es mi videojuego favorito?'];

		$users = null;
		$users = (is_countable($validar) > 0) ? $validar : null;
	}

	# Cambiar contraseña
	if (isset($_POST['newPass']) && isset($_POST['id_user']) && isset($_POST['actualPass'])) {
		$id_user = $_POST['id_user'];
		$pass = $_POST['actualPass'];
		$newPass = $_POST['newPass'];
		$data = "";

		if (!empty($id_user) && !empty($pass) && !empty($newPass)) {
			$records = $conn -> prepare('SELECT * FROM users WHERE id = :id');

			$records -> bindParam(':id', $id_user);
			$records -> execute();
			$results = $records->fetch(PDO::FETCH_ASSOC);

			if (password_verify($pass, $results['pass'])) {
				$sql = "UPDATE users SET pass = :pass WHERE id = :id";
			    
				$stmt = $conn -> prepare($sql);
				$stmt -> bindParam(':id', $id_user);
				$password = password_hash($newPass, PASSWORD_BCRYPT);
			    $stmt -> bindParam(':pass', $password);

				$data = $stmt -> execute() ? '¡Se ha cambiado tú contraseña exitosamente!' : '¡No se ha podido cambiar la contraseña!';

			} else {
				$data = "¡La información es incorrecta!";
			}
			die(json_encode($data));
		}
	}

	# Cambiar pregunta y respuesta 
	if (isset($_POST['pass']) && isset($_POST['id_user']) && isset($_POST['newPregunta']) && isset($_POST['newRespuesta'])) {
		$id_user = $_POST['id_user'];
		$pass = $_POST['pass'];
		$question = $_POST['newPregunta'];
		$respuesta = $_POST['newRespuesta'];
		$data = "";

		if (!empty($id_user) && !empty($pass) && !empty($question) && !empty($respuesta)) {
			$records = $conn -> prepare('SELECT * FROM users WHERE id = :id');

			$records -> bindParam(':id', $id_user);
			$records -> execute();
			$results = $records->fetch(PDO::FETCH_ASSOC);

			if (password_verify($pass, $results['pass'])) {
				$sql = "UPDATE users SET pregunta = :pregunta, respuesta = :respuesta WHERE id = :id";
			    
				$stmt = $conn -> prepare($sql);
				$stmt -> bindParam(':id', $id_user);
			    $stmt -> bindParam(':pregunta', $question);
			    $stmt -> bindParam(':respuesta', $respuesta);

				$data = $stmt -> execute() ? '¡Se ha cambiado tú pregunta y respuesta de seguridad exitosamente!' : '¡No se ha podido cambiar la pregunta y respuesta de seguridad!';

			} else {
				$data = "¡La información es incorrecta!";
			}
			die(json_encode($data));
		}
	}

	# Buzón 
	//$records = $conn -> prepare('INSERT INTO buzon (users, tipo_mensaje, mensaje) VALUES (:users, :tipo_mensaje, :mensaje)');
?>