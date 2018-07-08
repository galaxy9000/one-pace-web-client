<?php
header('Content-Type: application/json; charset=utf-8;');
require_once 'db_context.php';
require_once 'config.php';
require_once 'Authenticator.php';
$context = new db_context();
if(!Authenticator::authenticate($context, $_GET['token'], 2, $user)) {
	http_response_code(400);
} else {
	$context->connect();
	$issue = $context->read_issue($_GET['id']);
	if($issue == null) {
		http_response_code(400);
	} else {
		$context->update_issue($_GET['id'], ['completed' => 0]);
		$data = $context->list_episodeversions($user, $issue["episode_id"]);
		$context->disconnect();
		echo json_encode($data);
	}
}
?>
