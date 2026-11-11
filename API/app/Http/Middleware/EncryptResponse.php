<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter; // Import the class
// Do NOT import Facades\Crypt

class EncryptResponse
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
        $decodedKey = base64_decode(substr($key, 7)); // Remove 'base64:' prefix
        $cipher = config('app.cipher');

        $this->encrypter = new Encrypter($decodedKey, $cipher);
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $originalContent = $response->getContent();

        if ($originalContent && $response->isSuccessful()) {
            try {
                // Use our custom encrypter instance
                $encryptedContent = $this->encrypter->encryptString($originalContent);

                $response->setContent($encryptedContent);
                $response->header('Content-Type', 'text/plain');

            } catch (\Exception $e) {
                return response()->json(['error' => 'Could not encrypt response.'], 500);
            }
        }

        return $response;
    }
}
