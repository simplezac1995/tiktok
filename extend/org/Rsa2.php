<?php
namespace org;
class Rsa2
{
    private static $PRIVATE_KEY ="-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAgQCWi7AlMSLtqmSBUxLXHkTANN6/vLjmZ2pu7v5SEgfBnkHdHpVeGcePdXUbbuO8bRS6o2hHG+cZJeap5XwgAq875MFghdjZgz0KnQ4LDDwBrrSfp40ixq+kbvAhO8Z/zGtvuYNskuAyWp4qu9dqM+UkfYp8ObgrMQB01tJRq8JM69TzxuTKDvzL9otRHvoO2bgHn+osX/+n7vcWuSF/TLt843fUO82Un9YkuLBu1Bcro6czexmdJKfA1tlLbJ7mM00w2clrOl/aNV8pk3opTTqBc+XLYWuBV9YBcO145G9MvjxctmR+B9zqHyNZSdOujr+5zDhact0hw7+gHmC9OwIDAQABAoIBAA4oGl1J/0rdImfIj8imEKVptg4XQ8NaJy7CJ1pi3m7MGxta7wEkMydxFvU03MXWiB8QX0r/bo5JViEqpwpgDMM+p7v63LTTj0svr7J8kAnPq/+YIs0oV+Cl9c2W3I7KwcuVHrWlDo0vvVbkdJwYj7cSNmbwg6Fx7It8GVZZzkyi5hsdafBpxXzoyg+kZEo5/GSKWSDPuPzzSokDIBFMHEZcjysZLS4hBRxHTeX2VEaIl0WPYCDVqw79aRohQcC+4qtbTmC0sl67/GQKwAX5LlXg1XyC8aYiRgD/z0G7PQfkZh30iHI1GGIzmQlrSFw3YcC78MPiLXiUoDtn58iEm8ECgYEAtu7vUQgeRyPKj8STNl89N/RpIssICmZ96OOPnQk3id4IIn0jDjijbGJvfjiqjaQW2z7X62+w1oj0hwUE87kgu7SeY3PxsF/6Jmuh0kGSSSMKnRscrgR6ZmfjocWEoa0AY9FvtZjpfZjpQowG4C0VhNJP6j1VIT7VYzuPHpq3NlcCgYEAtIcqXjH4tDq0sTdvR4Fvt+jFJxGB0cpedEi2B+kAio8iNog0lemZqwqOcH4p9vDlVwJUobyb5OlsUS+3cYsIuRSKAaaoooa8cXASDhxT7m+ywx/RcDCqDW/h2TUlHJY1LJ1BvxR0pQzaMhMZj+k11bo9o7YUcFlCYEZnotD1+b0CgYAI1Z8vHaJAs0TMDqVBYfYV5rLRIFcCEZMRFTRRVCmfed6Qs9RmkmuqB1L1GI18C6mi6vUIhLtYkQKmJqLnllAzYHSpvua0Kt5szpPhEJOc/pk7nxySdtrmaSwAGwbdu/oh1/J6JalZDHJvaU3Hs8WvRWzglhevZFZv1WeDBdvFPwKBgQCG8ptLOpOtNk0oREYc77bxhUELSVz/1ATza/8Wvqg263Qpy3tzrOHAJ+3+TXFVNRJbDlTxaiom3g6oScEZUVM99wqK3Wglxg5LxfjZL3fWPw0kKz7GXLphvQbY9Y8+ZVJufUdObR4c2xSoZfqvxycFE2lMXam9qhuiGD8USJv3CQKBgBhmf7Je22dcRqafIhW/zSN8enEkp3MwlKtbuwpc6EJEA374fbjn2x+rIUn8gWxZXdfssrf9/W73mGB6kwd72ZqK2hSd1z6vovOD5G8wTumc0ilBIfElOU0AXrod91QA6hfE18QYnrFoBH8WcSORSqxGciYq6cDXmqGs9ZmWwuNY
-----END RSA PRIVATE KEY-----";
    private static $PUBLIC_KEY  ="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvZTqiIOBst2loJYcQ4Ds/RmqdIb/kJLbiSCu2grsQuCZWdcksxWpFJ++yrM1DBiHmRY3FiXDioN2bhBfrKVoISdD0lxQa3MZg4QGcJ9tQMWv5f6cWMC7942nTBf5qLX55KiAL7REwjfs0mlWUkf4AqZjYOPduJzLcg6tIBKJs506ziz07OoFPbQpysm7pPX5mbXo5zIZ0S5+oH3Bg28Z4wsw9BzwxuP13IPJtRWu0ZB0s+uFlQW/ETGKF/EzoeXVZGGxoWSHN5iE+758frOkjf6xr4M1g61HW5KFPxWsv88eR56i3HwoU+9gWvQcILQRz1dtxrkzbRoYOXLEB/bFzQIDAQAB
-----END PUBLIC KEY-----";
    /**
     * 获取私钥
     * @return bool|resource
     */
    private static function getPrivateKey()
    {
        $privKey = self::$PRIVATE_KEY;
        return openssl_pkey_get_private($privKey);
    }
    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey()
    {
        
        $publicKey = self::$PUBLIC_KEY;
        return openssl_pkey_get_public($publicKey);
    }
    /**
     * 创建签名
     * @param string $data 数据
     * @return null|string
     */
    public function createSign($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_sign($data, $sign, self::getPrivateKey(),OPENSSL_ALGO_SHA256 ) ? base64_encode($sign) : null;
    }
    /**
     * 验证签名
     * @param string $data 数据
     * @param string $sign 签名
     * @return bool
     */
    public function verifySign($data = '', $sign = '')
    {
        if (!is_string($sign) || !is_string($sign)) {
            return false;
        }
        return (bool)openssl_verify(
            $data,
            base64_decode($sign),
            self::getPublicKey(),
            OPENSSL_ALGO_SHA256
            );
    }
}