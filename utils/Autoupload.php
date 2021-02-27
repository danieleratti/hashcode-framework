<?php

namespace Utils;

class Autoupload
{
    public static $scriptContent = null;

    private static function getPK()
    {
        $f = trim(file_get_contents(".google_pk"));
        if (strlen($f) <= 10)
            Log::error("Missing .google_pk file with private key. Take it from authorization header in https://hashcode-judge.appspot.com/api/judge/v1/rounds");
        if (strpos($f, "Bearer") !== false)
            list(, $f) = explode("Bearer ", $f);
        return $f;
    }

    private static function req($method, $url, $body = null, $additionalHeaders = null)
    {
        $headers = [
            'Authority: hashcode-judge.appspot.com',
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'X-Goog-Encode-Response-If-Executable: base64',
            'X-Origin: https://hashcodejudge.withgoogle.com',
            'X-Clientdetails: appVersion=5.0%20(Macintosh%3B%20Intel%20Mac%20OS%20X%2010_13_6)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F88.0.4324.96%20Safari%2F537.36&platform=MacIntel&userAgent=Mozilla%2F5.0%20(Macintosh%3B%20Intel%20Mac%20OS%20X%2010_13_6)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F88.0.4324.96%20Safari%2F537.36',
            'Authorization: Bearer ' . self::getPK(),
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36',
            'X-Requested-With: XMLHttpRequest',
            'X-Javascript-User-Agent: google-api-javascript-client/1.1.0',
            'X-Referer: https://hashcodejudge.withgoogle.com',
            'Accept: */*',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
            'Referer: https://hashcode-judge.appspot.com/api/static/proxy.html',
            'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding: deflate',
        ];

        if ($additionalHeaders) {
            foreach ($additionalHeaders as $h) {
                $headers[] = $h;
            }
        }

        if (strpos($url, "http") === 0)
            $completeUrl = $url;
        else
            $completeUrl = 'https://hashcode-judge.appspot.com/' . $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_URL, $completeUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        if (!$response) {
            die('Error ' . $method . ' ' . $completeUrl . ': "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        curl_close($ch);
        $jresponse = json_decode($response, true);

        if (@$jresponse['error']['code'] == 401) {
            print_r($jresponse);
            Log::out("Missing or invalid OAuth Token. Please set .google_pk file! Retrying in 30s", 0, "red");
            sleep(30);
            return self::req($method, $url, $body, $additionalHeaders);
        }
        return $jresponse;
    }

    private static function createMultipartPostBody($delimiter, $postFields, $fileFields = array())
    {
        // form field separator
        $eol = "\r\n";
        $data = '';
        // populate normal fields first (simpler)
        foreach ($postFields as $name => $content) {
            $data .= "--$delimiter" . $eol;
            $data .= 'Content-Disposition: form-data; name="' . $name . '"';
            $data .= $eol . $eol; // note: double endline
            $data .= $content;
            $data .= $eol;
        }
        // populate file fields
        foreach ($fileFields as $name => $file) {
            $data .= "--$delimiter" . $eol;
            // fallback on var name for filename
            if (!array_key_exists('filename', $file)) {
                $file['filename'] = $name;
            }
            // "filename" attribute is not essential; server-side scripts may use it
            $data .= 'Content-Disposition: form-data; name="' . $name . '";' .
                ' filename="' . $file['filename'] . '"' . $eol;
            // this is, again, informative only; good practice to include though
            $data .= 'Content-Type: ' . $file['type'] . $eol;
            // this endline must be here to indicate end of headers
            $data .= $eol;
            // the file itself (note: there's no encoding of any kind)
            if (is_resource($file['content'])) {
                // rewind pointer
                rewind($file['content']);
                // read all data from pointer
                while (!feof($file['content'])) {
                    $data .= fgets($file['content']);
                }
                $data .= $eol;
            } else {
                // check if we are loading a file from full path
                if (strpos($file['content'], '@') === 0) {
                    $file_path = substr($file['content'], 1);
                    $fh = fopen(realpath($file_path), 'rb');
                    if ($fh) {
                        while (!feof($fh)) {
                            $data .= fgets($fh);
                        }
                        $data .= $eol;
                        fclose($fh);
                    }
                } else {
                    // use data as provided
                    $data .= $file['content'] . $eol;
                }
            }
        }
        // last delimiter
        $data .= "--" . $delimiter . "--$eol";
        return $data;
    }

    private static function getRemoteDatasets()
    {
        $res = self::req('GET', 'api/judge/v1/rounds');
        $ret = [];
        foreach ($res['items'][count($res['items'])-1]['dataSets'] as $ds) {
            list($c) = explode(" ", $ds['name']);
            $ret[strtolower($c)] = $ds['id'];
        }
        if (count($ret) <= 4) {
            print_r($ret);
            print_r($res);
            die("Rounds number incongruent");
        }
        return $ret;
    }

    private static function getDatasets()
    {
        $f = file_get_contents(".google_datasets");
        if ($f) {
            return json_decode($f, true);
        }
        $f = self::getRemoteDatasets();
        File::write('.google_datasets', json_encode($f));
        return $f;
    }

    private static function createUrl()
    {
        $res = self::req('GET', 'api/judge/v1/upload/createUrl');
        return $res['value'];
    }

    private static function remoteUpload($filename, $content)
    {
        $uploadUrl = self::createUrl();
        $delimiter = '----WebKitFormBoundary' . uniqid();
        $body = self::createMultipartPostBody($delimiter, [], array(
            'file' => array(
                'filename' => $filename,
                'type' => 'text/plain',
                'content' => $content,
                //'content' => '@data.txt',
                //'content' => fopen('data.txt', 'rb'),
                //'content' => 'raw contents',
            )
        ));
        $headers = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body)
        ];
        return self::req('POST', $uploadUrl, $body, $headers)['file'][0];
    }

    public static function submission($dataset, $filename = null, $content = null)
    {
        if (!self::$scriptContent)
            self::init();
        $datasets = self::getDatasets();
        $ds = $datasets[$dataset];

        if (!$filename) {
            $filename = $_SERVER["SCRIPT_NAME"];
        }

        $source = self::remoteUpload($filename . '.php', self::$scriptContent);
        $sub = self::remoteUpload($filename . '.php', $content);
        $ret = self::req('POST', "api/judge/v1/submissions?dataSet=$ds&submissionBlobKey=$sub&sourcesBlobKey=$source", '', null);
        Log::out("Upload completed!", 0, "green");
        return $ret;
    }

    public static function init()
    {
        global $CERBERUS_PARAMS;
        self::$scriptContent = file_get_contents($_SERVER["SCRIPT_NAME"]);
        if ($CERBERUS_PARAMS) {
            $_params = json_decode($CERBERUS_PARAMS, true);
            foreach ($_params as $k => $v)
                $params[] = "'$k' => '$v'";
            $params = "[" . implode(", ", $params) . "]";
            self::$scriptContent = str_replace("Cerberus::runClient", "Cerberus::runClient(" . $params . "); // Cerberus::runClient", self::$scriptContent);
        }
    }
}
