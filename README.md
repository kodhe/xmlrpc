# Kodhe XML-RPC (Client)

XML-RPC **client**-side library refactored from CodeIgniter 3's `Xmlrpc` (namespace `Kodhe\Framework\Xmlrpc`). Used to call methods on a remote XML-RPC server (e.g. WordPress `xmlrpc.php`, Blogger, legacy RPC web services). Two API styles are available: the CI3 procedural style (`server() → method() → request() → send_request()`) and the modern object-oriented `Client\XmlRpcClient::call()` with injectable transport/encoder/decoder.

## Installation

```bash
composer require kodhe/xmlrpc
```

Requirements: PHP >= 8.1, the `curl` extension (default transport), and the `xml` library.

## Quick Start

```php
<?php

declare(strict_types=1);

use Kodhe\Framework\Xmlrpc\Xmlrpc;

$rpc = new Xmlrpc();

$rpc->server('https://example.com/xmlrpc.php', 443);
$rpc->method('wp.getPostFormats');
$rpc->request([
    ['postId' => 1, 'username' => 'admin', 'password' => 'secret'],
]);

if ($rpc->send_request() !== false) {
    print_r($rpc->display_response());   // decoded result array
} else {
    echo $rpc->display_error();          // fault string
}
```

## Directory Structure

```
src/
├── Xmlrpc.php               # Main CI3-compatible class
├── Client/XmlRpcClient.php  # Modern OO client (setServer/call)
├── Message/                 # XmlRpcMessage (request), XmlRpcResponse
├── Encoder/ Decoder/        # encode/decode XML-RPC payloads
├── Transport/ + Factory/    # HTTP transport (curl/stream)
├── ValueObjects/XmlRpcValue.php
├── Contracts/ Support/ Exceptions/
```

## Usage

### 1. Using XML-RPC data types (`xmlrpc_*` constants)

```php
$rpc->request([
    $rpc->xmlrpc_int,      // <int>
    $rpc->xmlrpc_boolean,  // <boolean>
    $rpc->xmlrpc_string,   // <string>
    $rpc->xmlrpc_base64,   // <base64>
    $rpc->xmlrpc_struct,   // <struct> (assoc array)
    $rpc->xmlrpc_array,    // <array>  (list array)
    $rpc->xmlrpc_datetime, // <dateTime.iso8601>
]);
```

These public properties are identical to the legacy `CI_Xmlrpc::$xmlrpc_*` constants.

### 2. Proxy & timeout

```php
$rpc->server('http://rpc.example.com/xmlrpc.php', 80,
            'proxy.example.com', 8080);  // through a proxy
$rpc->timeout(15);                        // seconds
$rpc->set_debug(true);                    // log request/response
```

### 3. Modern style: `XmlRpcClient`

```php
use Kodhe\Framework\Xmlrpc\Client\XmlRpcClient;

$client = new XmlRpcClient();           // factory picks default transport/encoder
$client->setServer('https://example.com/xmlrpc.php', 443)
       ->setTimeout(10)
       ->setDebug(false);

$result = $client->call('system.listMethods');     // array | false
if ($result === false) {
    echo $client->getLastError();
}
```

The transport, encoder, and decoder can be swapped via the constructor as long as they implement the corresponding `Contracts/*Interface` (see `Factory/TransportFactory::create()` for the default examples).

### 4. Manually generating responses (small server-side utility)

```php
$rpc->send_error_message(404, 'Method not found'); // emit XML fault
$rpc->send_response(['ok' => true]);               // emit success XML
```

### 5. Reading a fault response

```php
$fault = $rpc->display_error();  // fault message string from the server
```

## API Reference (`Xmlrpc`)

| Method | Description |
|---|---|
| `initialize(array $config)` | Set preferences in bulk |
| `server($url, $port, $proxy, $proxy_port)` | Specify the endpoint |
| `timeout($seconds)` | Connection timeout |
| `method($function)` | Name of the remote method |
| `request($incoming)` | Build the parameters (array of values / typed) |
| `values_parsing($value)` | Normalize typed values |
| `send_request()` | Send; returns response object / FALSE |
| `display_response()` | Decoded result (array/scalar) |
| `display_error()` | Fault as a string |
| `send_error_message($n,$msg)` / `send_response($r)` | Reply XML output |
| `set_debug($flag)` | Debug mode |

## CodeIgniter 3 Compatibility

`Xmlrpc` is a drop-in replacement for `CI_Xmlrpc`: method names, `$xmlrpc_*` properties, and payload formats are the same. Only the namespace differs. CI helpers (`log_message`, curl config) are still used on the legacy transport path.

## Notes

- `send_request()` returns `false` both when the transport fails AND when the server sends a fault — use `display_error()` to tell them apart.
- Large payloads: raise the memory/time limits; the parser uses the internal SimpleXML implementation via `Decoder/`.

## Testing

```bash
vendor/bin/phpunit --filter Xmlrpc
```
