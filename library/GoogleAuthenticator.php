<?php

/**
 * Class GoogleAuthenticator
 *
 * $title  = "Example";
 * $secret = GoogleAuthenticator::genSecret();
 * $email  = "kuangzhiqiang@xiaomi.com";
 *
 * $QRCode    = GoogleAuthenticator::getQRCodeGoogleUrl($email, $secret, $title);
 * $is_verify = GoogleAuthenticator::verifyToken($secret, $token);
 */
class GoogleAuthenticator
{

    const key_regeneration = 30;   // Interval between key regeneration
    const otp_length       = 6;    // Length of the Token generated

    private static $lut = array(   // Lookup needed for Base32 encoding
        "A" => 0, "B" => 1,
        "C" => 2, "D" => 3,
        "E" => 4, "F" => 5,
        "G" => 6, "H" => 7,
        "I" => 8, "J" => 9,
        "K" => 10, "L" => 11,
        "M" => 12, "N" => 13,
        "O" => 14, "P" => 15,
        "Q" => 16, "R" => 17,
        "S" => 18, "T" => 19,
        "U" => 20, "V" => 21,
        "W" => 22, "X" => 23,
        "Y" => 24, "Z" => 25,
        "2" => 26, "3" => 27,
        "4" => 28, "5" => 29,
        "6" => 30, "7" => 31
    );

    /**
     * Generates a 16 digit secret key in base32 format
     * @param int $length
     * @return string
     */
    public static function genSecret($length = 16)
    {
        $b32 = "QWERTYUIOPASDFGHJKLZXCVBNM" . str_repeat("234567", 3);
        $s   = "";

        for ($i = 0; $i < $length; $i++)
            $s .= $b32[mt_rand(0, strlen($b32) - 1)];

        return $s;
    }

    /**
     * Returns the current Unix Timestamp devided by the key_regeneration
     * period.
     * @return float
     */
    public static function getTimeSlice()
    {
        return floor(time() / self::key_regeneration);
    }

    /**
     * Decodes a base32 string into a binary string.
     **/
    private static function _base32Encode($b32)
    {
        static $_info = array();
        if (!isset($_info[$b32]))
        {
            $b32 = strtoupper($b32);

            if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/', $b32, $match))
                throw new Exception('Invalid characters in the base32 string.');

            $l           = strlen($b32);
            $n           = 0;
            $j           = 0;
            $_info[$b32] = "";

            for ($i = 0; $i < $l; $i++)
            {

                $n = $n << 5;                // Move buffer left by 5 to make room
                $n = $n + self::$lut[$b32[$i]];    // Add value into buffer
                $j = $j + 5;                // Keep track of number of bits in buffer

                if ($j >= 8)
                {
                    $j = $j - 8;
                    $_info[$b32] .= chr(($n & (0xFF << $j)) >> $j);
                }
            }
        }

        return $_info[$b32];
    }

    /**
     * Takes the secret key and the timestamp and returns the one time
     * password.
     *
     * @param string $secret - Secret key.
     * @param integer $counter - Timestamp as returned by get_timestamp.
     * @return string
     * @throws Exception
     */
    public static function getToken($secret, $counter = null)
    {
        if ($counter === null)
        {
            $counter = self::getTimeSlice();
        }
        $bin_secret  = self::_base32Encode($secret);
        $bin_counter = pack('N*', 0) . pack('N*', $counter);        // Counter must be 64-bit int
        $hash        = hash_hmac('sha1', $bin_counter, $bin_secret, true);

        return str_pad(self::_oathTruncate($hash), self::otp_length, '0', STR_PAD_LEFT);
    }

    /**
     * Get QR-Code STR
     *
     * @param string $name
     * @param string $secret
     * @param string $title
     * @return string
     */
    public static function getQRCodeStr($name, $secret, $title = null)
    {
        $str = "otpauth://totp/{$name}?secret={$secret}";
        if (!empty($title))
        {
            $str .= "&issuer=" . urlencode($title);
        }
        return $str;
    }

    /**
     * Get QR-Code URL for image, from google charts
     *
     * @param string $name
     * @param string $secret
     * @param string $title
     * @return string
     */
    public static function getQRCodeGoogleUrl($name, $secret, $title = null)
    {
        $chl = self::getQRCodeStr($name, $secret, $title);
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($chl);
//        return "http://chart.apis.google.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($chl);
    }

    /**
     * Verifys a user inputted key against the current timestamp. Checks $window
     * keys either side of the timestamp.
     *
     * @param string $secret
     * @param string $token
     * @param integer $discrepancy This is the allowed time drift in 30 second units (8 means 4 minutes before or after)
     * @param null $timeStamp
     * @return boolean
     **/
    public static function verifyToken($secret, $token, $discrepancy = 1, $timeStamp = null)
    {
        if ($timeStamp === null)
            $timeStamp = self::getTimeSlice();
        else
            $timeStamp = (int) $timeStamp;

        for ($i = -$discrepancy; $i <= $discrepancy; $i++)
            if (self::getToken($secret, $timeStamp + $i) == $token)
                return true;


        return false;

    }

    /**
     * Extracts the OTP from the SHA1 hash.
     * @param string $hash
     * @return integer
     **/
    private static function _oathTruncate($hash)
    {
        $offset = ord($hash[19]) & 0xf;

        return (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::otp_length);
    }

}