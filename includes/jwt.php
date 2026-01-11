<?php

class JWT {
    
    public static function encode($payload, $key, $algorithm = 'HS256') {
        $header = json_encode(['typ' => 'JWT', 'alg' => $algorithm]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64UrlEncode($header);
        $payloadEncoded = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $key, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    public static function decode($jwt, $key, $algorithms = ['HS256']) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$header || !$payload) {
            throw new Exception('Invalid JWT payload');
        }
        
        if (!in_array($header['alg'], $algorithms)) {
            throw new Exception('Algorithm not allowed');
        }
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $key, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid signature');
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }
        
        // Check not before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new Exception('Token not yet valid');
        }
        
        return (object) $payload;
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    public static function createPayload($userId, $email, $role, $expiryHours = 24) {
        $now = time();
        return [
            'iss' => SITE_NAME,
            'aud' => BASE_URL,
            'iat' => $now,
            'exp' => $now + ($expiryHours * 3600),
            'user_id' => $userId,
            'email' => $email,
            'role' => $role
        ];
    }
}
?>