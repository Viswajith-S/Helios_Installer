<?php
$data = ['project_id' => 257];

$ch = curl_init('http://localhost/helios/other/Install%20manager/api/generate_swms.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
echo "Response: " . $response . "\n";
if(curl_errno($ch)) echo "Error: " . curl_error($ch) . "\n";
curl_close($ch);
