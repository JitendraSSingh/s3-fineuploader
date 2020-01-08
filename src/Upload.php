<?php

namespace App;
use Aws\S3\S3Client;

class Upload
{

    const EXPECTED_MAX_SIZE = 15000000;

    const EXPECTED_BUCKET_NAME = "jitu-playground";

    private $s3Client;

    public function __construct(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
    }


    public function signPolicy(string $policyStr)
    {
        $policyObj = json_decode($policyStr, true);
        if ($this->isPolicyValid($policyObj)) {
            $encodedPolicy = base64_encode($policyStr);
            $signature =  base64_encode(hash_hmac(
                'sha1',
                $encodedPolicy,
                getenv('AWS_SECRET_ACCESS_KEY'),
                true
            ));
            return array('policy' => $encodedPolicy, 'signature' => $signature);
        }
        return false;
    }
    

    private function isPolicyValid($policy)
    {
        $conditions = $policy["conditions"];
        $bucket = null;
        $parsedMaxSize = null;
        for ($i = 0; $i < count($conditions); ++$i) {
            $condition = $conditions[$i];
            if (isset($condition["bucket"])) {
                $bucket = $condition["bucket"];
            } else if (isset($condition[0]) && $condition[0] == "content-length-range") {
                $parsedMaxSize = $condition[2];
            }
        }
        return $bucket == self::EXPECTED_BUCKET_NAME && $parsedMaxSize == (string) self::EXPECTED_MAX_SIZE;
    }

    // Returns true if we should attempt to include a link
    // to a thumbnail in the uploadSuccess response.  In it's simplest form
    // (which is our goal here - keep it simple) we only include a link to
    // a viewable image and only if the browser is not capable of generating a client-side preview.
    public function shouldIncludeThumbnail(string $filename, bool $isBrowserPreviewCapable)
    {
        $isFileViewableImage = $this->isFileViewableImage($filename);
        return !$isBrowserPreviewCapable && $isFileViewableImage;
    }

    // Return true if it's likely that the associate file is natively
    // viewable in a browser.  For simplicity, just uses the file extension
    // to make this determination, along with an array of extensions that one
    // would expect all supported browsers are able to render natively.
    private function isFileViewableImage($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $viewableExtensions = array("jpeg", "jpg", "gif", "png");
        return in_array($ext, $viewableExtensions);
    }

    // This is not needed if you don't require a callback on upload success.
    public function verifyFileInS3(bool $includeThumbnail, string $bucket, string $key)
    {
        // If utilizing CORS, we return a 200 response with the error message in the body
        // to ensure Fine Uploader can parse the error message in IE9 and IE8,
        // since XDomainRequest is used on those browsers for CORS requests.  XDomainRequest
        // does not allow access to the response body for non-success responses.
        if (defined(self::EXPECTED_MAX_SIZE) && $this->getObjectSize($bucket, $key) > self::EXPECTED_MAX_SIZE) {
            // You can safely uncomment this next line if you are not depending on CORS
            $this->deleteObject($bucket, $key);
            return array("error" => "File is too big!", "preventRetry" => true);
        } else {
            $link = $this->getTempLink($bucket, $key);
            $response = array("tempLink" => $link);
            if ($includeThumbnail) {
                $response["thumbnailUrl"] = $link;
            }
            return $response;
        }
    }

    private function getObjectSize($bucket, $key)
    {
        $objInfo = $this->s3Client->headObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
        return $objInfo['ContentLength'];
    }

    // Provide a time-bombed public link to the file.
    private function getTempLink($bucket, $key)
    {
        $cmd = $this->s3Client->getCommand('GetObject',[
            'Bucket' => $bucket,
            'Key' => $key
        ]);
        $request = $this->s3Client->createPresignedRequest($cmd, '+15 minutes');
        return (string)$request->getUri();
    }

    // Only needed if the delete file feature is enabled
    public function deleteObject(string $bucket, string $key) {
        $this->s3Client->deleteObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
    }
}
