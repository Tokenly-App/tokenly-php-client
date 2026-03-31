# Tokenly PHP

PHP client library for the [Tokenly](https://tokenlyapp.com) API.

## Requirements

- PHP 8.3+
- Guzzle HTTP 7.x

## Installation

```bash
composer require tokenly-app/tokenly-php-client
```

## Getting credentials

Register at [tokenlyapp.com](https://tokenlyapp.com), create an app, and obtain your **App Key** and **App Secret** from the SDK page.

## Usage

### Instantiate the client

```php
use Tokenly\TokenlyClient;

$client = new TokenlyClient(
    appKey: 'your-app-key',
    appSecret: 'your-app-secret',
);
```

### Get token balance

Retrieve the current token balance for your app.

```php
use Tokenly\TokenlyClient;

$client = new TokenlyClient('your-app-key', 'your-app-secret');

$balance = $client->getTokenBalance();

echo $balance->balance;        // net balance
echo $balance->tokensSent;     // total tokens sent
echo $balance->tokensReceived; // total tokens received
echo $balance->appId;          // your app ID
```

### Gift tokens

Send tokens to another registered app using its app code.

```php
use Tokenly\TokenlyClient;

$client = new TokenlyClient('your-app-key', 'your-app-secret');

$response = $client->giftTokens('TARGET_APP_CODE');

echo $response->message;                   // "Tokens gifted successfully"
echo $response->transaction->id;           // transaction ID
echo $response->transaction->targetAppCode; // recipient app code
echo $response->transaction->createdAt;    // ISO 8601 timestamp
```

### Error handling

Every method throws a specific exception on failure. All exceptions extend `TokenlyException`, so you can catch them individually or as a group.

```php
use Tokenly\TokenlyClient;
use Tokenly\Exceptions\AuthenticationException;
use Tokenly\Exceptions\ForbiddenException;
use Tokenly\Exceptions\TokenlyException;
use Tokenly\Exceptions\ValidationException;

$client = new TokenlyClient('your-app-key', 'your-app-secret');

try {
    $response = $client->giftTokens('TARGET_APP_CODE');
} catch (AuthenticationException $e) {
    // 401 — invalid or missing credentials
} catch (ForbiddenException $e) {
    // 403 — app is inactive or lacks permission
} catch (ValidationException $e) {
    // 400 — invalid request (e.g. gifting tokens to yourself)
} catch (TokenlyException $e) {
    // any other API error
}
```

## API reference

### `giftTokens(string $targetAppCode): GiftTokensResponse`

Sends tokens to another app.

| Parameter | Type | Description |
|---|---|---|
| `$targetAppCode` | `string` | The recipient app's unique code |

**`GiftTokensResponse` properties**

| Property | Type | Description |
|---|---|---|
| `$success` | `bool` | Whether the request succeeded |
| `$message` | `string` | Human-readable result message |
| `$transaction` | `Transaction` | The created transaction |

**`Transaction` properties**

| Property | Type | Description |
|---|---|---|
| `$id` | `int` | Transaction ID |
| `$senderAppId` | `int` | ID of the sending app |
| `$targetAppCode` | `string` | Code of the recipient app |
| `$createdAt` | `string` | ISO 8601 creation timestamp |

---

### `getTokenBalance(): TokenBalanceResponse`

Returns the token balance for your app.

**`TokenBalanceResponse` properties**

| Property | Type | Description |
|---|---|---|
| `$success` | `bool` | Whether the request succeeded |
| `$appId` | `int` | Your app's ID |
| `$tokensSent` | `int` | Total tokens sent |
| `$tokensReceived` | `int` | Total tokens received |
| `$balance` | `int` | Net balance (`received - sent`) |

## Running tests

```bash
./vendor/bin/pest
```
