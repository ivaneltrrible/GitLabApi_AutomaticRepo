<?php

$ip_server = $argv[1];
$user_server = $argv[2];
$pass_server = $argv[3];
$db_server = $argv[4];

$con_local = mysqli_connect($ip_server, $user_server, $pass_server, $db_server) or die('No se pudo conectar');

$query = ('SELECT * from servers WHERE domain != "" and active = "Y" ');

$sql = $con_local->query($query);

//########################### API PARA OBTENER REPOS #####################################

$url = "https://gitlab.com/api/v4/users/5279164/projects?pagination=keyset&per_page=100&order_by=id&sort=asc";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "PRIVATE-TOKEN: gKtsidkUkKLiKHxbxzzb"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
$projects = json_decode($result, true);

$nameProyectos = array();
$idProyectos = array();
$nameProyectos = array();
foreach ($projects as $project) {
 // var_dump($result);
 $nameProyectos[] = $project['name'];
 $idProyectos[] = $project['id'];
}

//########################### API PARA CREAR REPOSITORIOS con los nombres de la ip #####################################

// var_dump($sql->num_rows);
// var_dump($result['ip']);
$x = 0;
while ($resultado = $sql->fetch_array(MYSQLI_ASSOC)) {

 $args2 = json_encode(array("name" => $resultado['ip'], "user_id" => "5279164"));
//  var_dump($args2);
 $url2 = "https://gitlab.com/api/v4/projects";
 $ch2 = curl_init();
 curl_setopt($ch2, CURLOPT_URL, $url2);
 curl_setopt($ch2, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "PRIVATE-TOKEN: gKtsidkUkKLiKHxbxzzb"));
 if (isset($args2)) {
  curl_setopt($ch2, CURLOPT_POST, 1);
  // curl_setopt($ch, CURLOPT_POSTFIELDS,$args2);
  curl_setopt($ch2, CURLOPT_POSTFIELDS, $args2);

  curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

  $result2 = curl_exec($ch2);
//   var_dump($result2);
  curl_close($ch2);
  $addProject = json_decode($result2, true);

 }
 for ($i = 0; $i < count($nameProyectos); $i++) {
  if ($nameProyectos[$i] == $resultado['ip']) {
   $args2 = json_encode(array("branch" => date('Y/m/d'), "ref" => "master"));
   // var_dump($args2);
   $url2 = "https://gitlab.com/api/v4/projects/$idProyectos[$i]/repository/branches";
   $ch2 = curl_init();
   curl_setopt($ch2, CURLOPT_URL, $url2);
   curl_setopt($ch2, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "PRIVATE-TOKEN: gKtsidkUkKLiKHxbxzzb"));
   if (isset($args2)) {
    curl_setopt($ch2, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS,$args2);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $args2);

    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

    $result2 = curl_exec($ch2);
    // var_dump($result2);
    curl_close($ch2);
    $addProject = json_decode($result2, true);

   }
  }
 }
 if ($resultado['system'] == "Vicidial" || $resultado['system'] == "Integra") {
  $ruta = "/srv/www/htdocs/";
 } elseif ($resultado['system'] == "SistemaWeb" || $resultado['system'] == "Issabel") {
  $ruta = "/var/www/html/";
 }

 //SE GUARDA LA IP QUE SE ESTA EJECUTANDO ACTUALMENTE EN EL WHILE
 $ipServer = $resultado['ip'];

 //SE CREA LA RAMA EN EL REPO DE GITLAB CON EL NOMBRE DEL DIA QUE SE REALIZO EL RESPALDO
 $rama = date('Y/m/d');

 //LA IP SE LE QUITAN LOS . Y SE LE REMPLZA POR GUIONES PARA QUE GITLAB LO RECONOZCA COMO UNA URL VALIDA
 $ipServerGuion = str_replace('.', '-', $ipServer);

//  echo $x . "\n";

 //SE VALIDA QUE NO SEAN NINGUNO DE ESOS SERVIDORES YA QUE ESOS POR EL MOMENTO ESTAN PROHIBIDOS
 if ($ipServer != '104.248.239.229' && $ipServer != '68.183.120.226' && $ipServer != '10.8.0.46') {
  echo $ipServerGuion;

  //SE VALIDA SI EL SERVIDOR TIENE INSTALADO ALGUNA VERSION DE GIT SI NO -- SE INSTALA RPM Y DESPUES GIT A LA VERSION MAS RECIENTE
  $response = shell_exec("ssh root@$ipServer 'git --version'");
  if (substr($response, 0, 11) != 'git version' && $resultado['system'] == 'Issabel') {
   echo shell_exec("ssh root@$ipServer  'sudo yum -y install https://packages.endpoint.com/rhel/7/os/x86_64/endpoint-repo-1.7-1.x86_64.rpm;'  'sudo yum -y install git'");
  }

  //SE CREA COPIA DE SEGURIDAD DE DB DEPENDIENDO EL SISTEMA 
  if($resultado['system'] == 'Issabel'){
    shell_exec("ssh root@$ipServer 'mysqldump  --password='netillo123' asterisk > /var/www/html/respaldo.sql'");
  }elseif ($resultado['system'] == 'Integra' || $resultado['system'] == 'Vicidial') {
    shell_exec("ssh root@$ipServer 'mysqldump -u cron --password='1234' asterisk > /var/www/htdocs/respaldo.sql'");
  }
  exit('termino wey');

  // comando que conecta con el servidor, crea las credenciales de Git despues genera un repositorio y hace los comandos de conectarse a la rama y generar una rama con el nombre de la fecha actual, por ultimo elimina las credenciales de Git
  shell_exec("ssh root@$ipServer 'echo 'https://victornbx:Nbx20x19x@gitlab.com' > .git-credentials;' 'cd $ruta;' 'rm -f .git;' 'git init;' 'git config --global user.email 'v.valdovinos@nbxsoluciones.com';' 'git config --global user.name 'victornbx';' 'git config credential.helper store;' 'git remote add origin https://gitlab.com/victornbx/$ipServerGuion;' 'git checkout -b $rama;' 'git add .;' 'git commit -m 'Initial' ;' 'git push -f origin $rama;' 'cd;' 'rm -f .git-credentials;' 'exit;'");
 }

}

//########################### API PARA OBTENER ARCHIVOS DEL REPOSITORIO #####################################

// $url = "https://gitlab.com/api/v4/projects/19971924/repository/files/app%Accounts?ref=dev";
// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL,$url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","PRIVATE-TOKEN: gKtsidkUkKLiKHxbxzzb"));

// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// $result = curl_exec ($ch);
// curl_close ($ch);
// $projects = json_decode($result,true);

// var_dump($result);

//########################### API PARA CREAR RAMAS #####################################

// $args2 = json_encode(array("branch"=>"dev_rama","ref"=>"master"));
// // var_dump($args2);
// $url2 = "https://gitlab.com/api/v4/projects/20267855/repository/branches";
// $ch2 = curl_init();
// curl_setopt($ch2, CURLOPT_URL,$url2);
// curl_setopt($ch2, CURLOPT_HTTPHEADER, array("Content-Type: application/json","PRIVATE-TOKEN: gKtsidkUkKLiKHxbxzzb"));
// if(isset($args2)){
//     curl_setopt($ch2, CURLOPT_POST, 1);
//     // curl_setopt($ch, CURLOPT_POSTFIELDS,$args2);
//     curl_setopt($ch2, CURLOPT_POSTFIELDS, $args2);

//     curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

//     $result2 = curl_exec($ch2);
//     var_dump($result2);
//     curl_close ($ch2);
//     $addProject = json_decode($result2,true);

// }
