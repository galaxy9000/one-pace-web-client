<?php
header('Content-Type: application/json; charset=utf-8;');
require_once 'db_context.php';
require_once 'config.php';
$name = strtolower($_GET['name']);
$pass = $_GET['password'];
$pass_hashed = sha1($pass . $name . SALT);
$context = new db_context();
$context->connect();
$statement = $context->prepare("select * from users where lower(name) = ? and password = ?;");
$statement->bind_param('ss', $name, $pass_hashed);
$rows = $context->get_result($statement);
$context->disconnect();

if(sizeof($rows) > 0) {
	$user = $rows[0];
	$token = sha1(time() . SALT);
	$context->connect();
	$stmt = $context->prepare("update users set token = '" . $token . "' where id = " . $user["id"] . ";");
	$context->execute($stmt);
	$user = [
		"id" => $user["id"],
		"role" => $user["role"],
		"name" => $user["name"],
		"token" => $token
	];
	$data = $context->list_progress_episodes($user);
	$context->disconnect();
	$data['user'] = $user;
	echo json_encode($data);
} else {
	http_response_code(400);
}
?>
