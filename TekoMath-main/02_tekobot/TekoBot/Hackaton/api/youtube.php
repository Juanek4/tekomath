<?php
header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode(['success'=>false,'error'=>'La búsqueda de videos fue retirada de TekoBot.']);
