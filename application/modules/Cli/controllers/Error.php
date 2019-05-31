<?php

/**
 * 错误处理
 *
 * @package    Controller
 * @author     baojun <baojun4545@sina.com>
 */
class ErrorController extends Yaf_Controller_Abstract {

    public function ErrorAction(Exception $exception) { //将来
        echo "\033[35m[" . get_class($exception) . "] ("
            . $exception->getCode() . ")\033[0m";
        echo "\r\n\033[33m" . $exception->getMessage() . "\033[0m\r\n";
        echo "in:" . $exception->getFile() . ' (' . $exception->getLine() . ")\r\n";

        if (method_exists($exception, 'getMetadata')) {
            $metadata = $exception->getMetadata();
            echo 'Metadata:';
            print_r($metadata);
            echo "\r\n";
        }

        echo "\r\n\033[36m" . $exception->getTraceAsString() . "\033[0m\r\n";

        exit;
        return false;
    }

}
