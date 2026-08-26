<?php

class R2Uploader
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }


    public function upload($archivo, $rutaDestino)
    {
        if (!file_exists($archivo)) {
            throw new Exception("Archivo no existe");
        }


        $endpoint  = $this->config['endpoint'];
        $bucket    = $this->config['bucket'];
        $accessKey = $this->config['access_key'];
        $secretKey = $this->config['secret_key'];


        $host = parse_url($endpoint, PHP_URL_HOST);

        $uri = "/" . $bucket . "/" . $rutaDestino;

        $url = $endpoint . "/" . $bucket . "/" . $rutaDestino;


        $region  = "auto";
        $service = "s3";


        $payloadHash = "UNSIGNED-PAYLOAD";

        $amzDate = gmdate("Ymd\THis\Z");
        $date    = gmdate("Ymd");



        $canonicalHeaders =
            "host:" . $host . "\n" .
            "x-amz-content-sha256:" . $payloadHash . "\n" .
            "x-amz-date:" . $amzDate . "\n";


        $signedHeaders =
            "host;x-amz-content-sha256;x-amz-date";


        $canonicalRequest =
            "PUT\n" .
            $uri . "\n\n" .
            $canonicalHeaders . "\n" .
            $signedHeaders . "\n" .
            $payloadHash;



        $algorithm = "AWS4-HMAC-SHA256";


        $scope =
            $date . "/" .
            $region . "/" .
            $service . "/" .
            "aws4_request";


        $stringToSign =
            $algorithm . "\n" .
            $amzDate . "\n" .
            $scope . "\n" .
            hash("sha256", $canonicalRequest);



        $kDate = hash_hmac(
            "sha256",
            $date,
            "AWS4".$secretKey,
            true
        );

        $kRegion = hash_hmac(
            "sha256",
            $region,
            $kDate,
            true
        );

        $kService = hash_hmac(
            "sha256",
            $service,
            $kRegion,
            true
        );

        $kSigning = hash_hmac(
            "sha256",
            "aws4_request",
            $kService,
            true
        );


        $signature = hash_hmac(
            "sha256",
            $stringToSign,
            $kSigning
        );



        $authorization =
            $algorithm .
            " Credential=" .
            $accessKey .
            "/" .
            $scope .
            ", SignedHeaders=" .
            $signedHeaders .
            ", Signature=" .
            $signature;



        $fp = fopen($archivo, "rb");

        if (!$fp) {
            throw new Exception(
                "No se pudo abrir archivo"
            );
        }



        $ch = curl_init($url);


        curl_setopt_array($ch, [

            CURLOPT_CUSTOMREQUEST => "PUT",

            CURLOPT_UPLOAD => true,

            CURLOPT_INFILE => $fp,

            CURLOPT_INFILESIZE => filesize($archivo),


            CURLOPT_HTTPHEADER => [

                "Host: ".$host,

                "Authorization: ".$authorization,

                "x-amz-date: ".$amzDate,

                "x-amz-content-sha256: ".$payloadHash,

                "Content-Length: ".filesize($archivo),

                "Expect:"

            ],


            CURLOPT_CONNECTTIMEOUT => 120,

            CURLOPT_TIMEOUT => 1800,


            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,


            CURLOPT_TCP_KEEPALIVE => 1,

            CURLOPT_TCP_KEEPIDLE => 30,

            CURLOPT_TCP_KEEPINTVL => 15,


            CURLOPT_RETURNTRANSFER => true

        ]);



        $respuesta = curl_exec($ch);



        if ($respuesta === false) {

            $error = curl_error($ch);

            fclose($fp);

            throw new Exception(
                "CURL ERROR: ".$error
            );
        }



        $codigo = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


        fclose($fp);



        if ($codigo < 200 || $codigo >= 300) {

            throw new Exception(
                "R2 HTTP ".$codigo.": ".$respuesta
            );
        }



        return true;

    }
}
