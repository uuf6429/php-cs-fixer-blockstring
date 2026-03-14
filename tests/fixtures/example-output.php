<?php declare(strict_types=1);

/**
 * Demo: fetch users from DB and generate a JS snippet with JSON data
 */

$sql = <<<'SQL'
SELECT id, name, email
FROM users
WHERE status = 'active'
ORDER BY created_at DESC
SQL;

/** @var PDO $pdo */
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$jsonUsers = json_encode($users);

$json = <<<"JSON"
	{
	  "ascending": false,
	  "users": {$jsonUsers}
	}
	
	JSON;

echo <<<JS
(function(){
	const userData={$json};
	console.log("Active users:", userData.users);
})();
JS;
