<?php
	$host = 'localhost';
	$basededatos = 'database';
	$usuario = 'root';
	$contraseña = ''; 
	//$contraseña = '!JesusHT12015';

	$conexion = new mysqli($host, $usuario,$contraseña, $basededatos);

	$tabla="";
	$query="SELECT * FROM users ORDER BY id";

	if(isset($_POST['name'])){
		$q=$conexion->real_escape_string($_POST['name']);
		$query="SELECT * FROM users WHERE 
			id LIKE '%".$q."%' OR
			name LIKE '%".$q."%'";
	}

	$buscarAlumnos=$conexion->query($query);
	if ($buscarAlumnos->num_rows > 0){
		while($filaAlumnos= $buscarAlumnos->fetch_assoc())
		{
			$tabla.=
			'<tr>
				<td scope="row">'.$filaAlumnos['id'].'</td>
				<td>'.$filaAlumnos['name'].'</td>
				<td>
					<form action="index-administrador.php" method="POST" id="userDelete'.$filaAlumnos['id'].'">
						<input type="hidden" name="eliminar-user" value="'.$filaAlumnos['id'].'">
						<button type="button" class="submit" onclick="userDelete('.$filaAlumnos['id'].')"><i class="bi bi-trash-fill"></i></button>	
					</form>
				</td>
			</tr>
			';
		}
	} else{
			$tabla ="<tr>
						<td></td>
						<td>No se encontraron coincidencias con sus criterios de búsqueda.</td>
						<td></td>
		 			</tr>";
	}
	echo $tabla;
?>
