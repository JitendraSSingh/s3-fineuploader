<?php
namespace App\Controllers;

use Slim\Views\Twig as View;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Upload;

class HomeController
{
    protected $view;
    protected $upload;

    public function __construct(View $view, Upload $upload)
    {
        $this->view = $view;
        $this->upload = $upload;
    }

    public function index(Request $request, Response $response)
    {
        return $this->view->render($response, 'home.twig');
    }

    public function test(Request $request, Response $response)
    {
        return $response->withJson(['test' => 'OK']);
    }

    public function signPolicyDocument(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $signedPolicy = $this->upload->signPolicy(json_encode($body));
        if($signedPolicy !== false){
            return $response->withJson($signedPolicy);
        }
        return $response->withJson(['invalid' => true]);
    }

    public function verifyUpload(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $filename = $body['name'];
        $bucket = $body['bucket'];
        $key = $body['key'];
        $isBrowserPreviewCapable = $body['isBrowserPreviewCapable'];
        $s3response = $this->upload->verifyFileInS3($this->upload->shouldIncludeThumbnail($filename, $isBrowserPreviewCapable),$bucket, $key);
        if(array_key_exists('error', $s3response)){
            return $response->withJson($s3response, 500);
        }
        return $response->withJson($s3response);
    }

    public function deleteUpload(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $bucket = $body['bucket'];
        $key = $body['key'];
        $s3Response = $this->upload->deleteObject($bucket, $key);
        return $response->withJson($s3Response);
    }

}