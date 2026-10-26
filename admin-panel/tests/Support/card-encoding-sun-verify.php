<?php

declare(strict_types=1);

// Independent synthetic encoder emulator -> actual server verifier. No DB/hardware.
require __DIR__.'/../../vendor/autoload.php';

use App\Services\Cards\Crypto\AesCmac;
use App\Services\Cards\Crypto\SunVerifier;

$input = json_decode(stream_get_contents(STDIN), true, 8, JSON_THROW_ON_ERROR);
try {
    $result = (new SunVerifier(new AesCmac))->verify($input['picc'], $input['mac'], hex2bin($input['metadata_key']), hex2bin($input['file_read_key']), $input['uid']);
    echo json_encode(['counter' => $result['counter']], JSON_THROW_ON_ERROR);
} catch (Throwable) {
    fwrite(STDERR, "Synthetic chip proof verification refused.\n");
    exit(1);
}
