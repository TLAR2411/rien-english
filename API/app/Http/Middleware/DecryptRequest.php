<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class DecryptRequest
{
    /**
     * Our custom encrypter instance.
     * @var \Illuminate\Encryption\Encrypter
     */
    protected $encrypter;

    public function __construct()
    {
        // Initialize the encrypter with our custom key
        $key = config('app.api_encryption_key');

        // Key MUST be base64 decoded before use
        $decodedKey = base64_decode(substr($key, 7)); // Remove 'base64:' prefix
        $cipher = config('app.cipher');

        $this->encrypter = new Encrypter($decodedKey, $cipher);
    }

    public function handle(Request $request, Closure $next)
    {
        if (empty($request->getContent())) {
            return $next($request);
        }

        try {
            // Get the encrypted content
            $encryptedContent = $request->getContent();

            // Decrypt using Laravel's decrypt method (handles the payload format)
            $decryptedPayload = $this->encrypter->decrypt($encryptedContent);

            // If it's already an array (Laravel's decrypt returns the original data type)
            if (is_array($decryptedPayload)) {
                $request->merge($decryptedPayload);
            } else {
                // If it's a JSON string, decode it
                $data = json_decode($decryptedPayload, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['error' => 'Invalid JSON payload.'], 400);
                }

                $request->merge($data);
            }

        } catch (DecryptException $e) {
            return response()->json(['error' => 'Unauthorized: Invalid payload. ' . $e->getMessage()], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Decryption error: ' . $e->getMessage()], 400);
        }

        return $next($request);
    }
}
