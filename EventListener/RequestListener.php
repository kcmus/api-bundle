<?php

namespace RJP\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

define('ARRAY_KEY_FC_LOWERCASE', 25); //FOO => fOO
define('ARRAY_KEY_FC_UPPERCASE', 20); //foo => Foo
define('ARRAY_KEY_UPPERCASE', 15); //foo => FOO
define('ARRAY_KEY_LOWERCASE', 10); //FOO => foo
define('ARRAY_KEY_USE_MULTIBYTE', true); //use mutlibyte functions

class RequestListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $this->container->get("request");

        if (preg_match('/application\/vnd.rjp-v((\d+\\.)?(\d+\\.)?(\\*|\d+))\+(\w+)/', $request->headers->get('Accept'), $matches))
        {
            $request->headers->set('Accept', 'application/'.$matches[count($matches) - 1]);
            $request->headers->set('api-accept-version', $matches[1]);
        }

        if (preg_match('/application\/vnd.rjp-v((\d+\\.)?(\d+\\.)?(\\*|\d+))\+(\w+)/', $request->headers->get('Content-Type'), $matches))
        {
            $request->headers->set('Content-Type', 'application/'.$matches[count($matches) - 1]);
            $request->headers->set('api-content-type-version', $matches[1]);
        }

        try {

          $data = json_decode($request->getContent(),true);

          if ($data) {

            $data = $this->array_change_key_case_ext($data, ARRAY_KEY_FC_LOWERCASE);
            $req = $event->getRequest();

            $req->initialize(
              $req->query->all(),
              $req->request->all(),
              $req->attributes->all(),
              $req->cookies->all(),
              $req->files->all(),
              $req->server->all(),
              json_encode($data, JSON_PRETTY_PRINT)
            );

          }
        }
        catch (\Exception $e)  {

        }
    }

  /**
   * @param array $array
   * @param int $case
   * @param bool $useMB
   * @param string $mbEnc
   * @return array
   */
  private function array_change_key_case_ext(array $array, $case = 10, $useMB = false, $mbEnc = 'UTF-8') {
    $newArray = array();

    //for more speed define the runtime created functions in the global namespace
    //get function
    if($useMB === false) {
      $function = 'strToUpper'; //default
      switch($case) {
        //first-char-to-lowercase
        case 25:
          //maybee lcfirst is not callable
          if(!function_exists('lcfirst'))
            $function = create_function('$input', '
                            return strToLower($input[0]) . substr($input, 1, (strLen($input) - 1));
                        ');
          else $function = 'lcfirst';
          break;

        //first-char-to-uppercase
        case 20:
          $function = 'ucfirst';
          break;

        //lowercase
        case 10:
          $function = 'strToLower';
      }
    } else {
      //create functions for multibyte support
      switch($case) {
        //first-char-to-lowercase
        case 25:
          $function = create_function('$input', '
                        return mb_strToLower(mb_substr($input, 0, 1, \'' . $mbEnc . '\')) .
                            mb_substr($input, 1, (mb_strlen($input, \'' . $mbEnc . '\') - 1), \'' . $mbEnc . '\');
                    ');

          break;

        //first-char-to-uppercase
        case 20:
          $function = create_function('$input', '
                        return mb_strToUpper(mb_substr($input, 0, 1, \'' . $mbEnc . '\')) .
                            mb_substr($input, 1, (mb_strlen($input, \'' . $mbEnc . '\') - 1), \'' . $mbEnc . '\');
                    ');

          break;

        //uppercase
        case 15:
          $function = create_function('$input', '
                        return mb_strToUpper($input, \'' . $mbEnc . '\');
                    ');
          break;

        //lowercase
        default: //case 10:
          $function = create_function('$input', '
                        return mb_strToLower($input, \'' . $mbEnc . '\');
                    ');
      }
    }

    //loop array
    foreach($array as $key => $value) {

      $key = str_replace(' ', '', $key); //Remove spaces.  Not fully camel case ready.

      if(is_array($value)) //$value is an array, handle keys too
        $newArray[$function($key)] = $this->array_change_key_case_ext($value, $case, $useMB);
      elseif(is_string($key))
        $newArray[$function($key)] = $value;
      else $newArray[$key] = $value; //$key is not a string
    } //end loop

    return $newArray;
  }
}