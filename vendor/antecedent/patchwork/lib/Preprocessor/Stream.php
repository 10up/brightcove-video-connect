<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor;

use Patchwork\Exceptions;

class Stream
{
    const PROTOCOL = "file";
    const STREAM_OPEN_FOR_INCLUDE = 128;
    
    public $context;
    public $resource;

    static function wrap()
    {
        stream_wrapper_unregister(self::PROTOCOL);
        stream_wrapper_register(self::PROTOCOL, __CLASS__);
    }
    
    static function unwrap()
    {
        stream_wrapper_restore(self::PROTOCOL);
    }

    function stream_open($path, $mode, $options, &$openedPath)
    {
        $this->unwrap();
        if (($options & self::STREAM_OPEN_FOR_INCLUDE) && shouldPreprocess($path)) {
            $this->resource = preprocessAndOpen($path);
            $this->wrap();
            return true;
        }
        if (isset($this->context)) {
            $this->resource = fopen($path, $mode, $options, $this->context);
        } else {
            $this->resource = fopen($path, $mode, $options);
        }
        $this->wrap();
        return $this->resource !== false;
    }
    
    function stream_close()
    {
        return fclose($this->resource);
    }
    
    function stream_eof()
    {
        return feof($this->resource);
    }
    
    function stream_flush()
    {
        return fflush($this->resource);
    }

    function stream_read($count)
    {
        return fread($this->resource, $count);
    }
    
    function stream_seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->resource, $offset, $whence) === 0;
    }
    
    function stream_stat()
    {
        return fstat($this->resource);
    }
    
    function stream_tell()
    {
        return ftell($this->resource);
    }
    
    function url_stat($path, $flags)
    {
        $this->unwrap();
        $result = @stat($path);
        $this->wrap();
        return $result;
    }
  
    function dir_closedir()
    {
        closedir($this->resource);
        return true;
    }
    
    function dir_opendir($path, $options)
    {
        $this->unwrap();
        if (isset($this->context)) {
            $this->resource = opendir($path, $this->context);
        } else {
            $this->resource = opendir($path);
        }
        $this->wrap();
        return $this->resource !== false;
    }
    
    function dir_readdir()
    {
        return readdir($this->resource);
    }
    
    function dir_rewinddir()
    {
        rewinddir($this->resource);
        return true;
    }
    
    function mkdir($path, $mode, $options)
    {
        $this->unwrap();
        if (isset($this->context)) {
            $result = mkdir($path, $mode, $options, $this->context);
        } else {
            $result = mkdir($path, $mode, $options);
        }
        $this->wrap();
        return $result;
    }
    
    function rename($path_from, $path_to)
    {
        $this->unwrap();
        if (isset($this->context)) {
            $result = rename($path_from, $path_to, $this->context);
        } else {
            $result = rename($path_from, $path_to);
        }
        $this->wrap();
        return $result;
    }
    
    function rmdir($path, $options)
    {
        $this->unwrap();
        if (isset($this->context)) {
            $result = rmdir($path, $this->context);
        } else {
            $result = rmdir($path);
        }
        $this->wrap();
        return $result;
    }
    
    function stream_cast($cast_as)
    {
        return $this->resource;
    }
    
    function stream_lock($operation)
    {
        return flock($this->resource, $operation);
    }
    
    function stream_set_option($option, $arg1, $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->resource, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->resource, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->resource, $arg1);
            case STREAM_OPTION_READ_BUFFER:
                return stream_set_read_buffer($this->resource, $arg1);
            case STREAM_OPTION_CHUNK_SIZE:
                return stream_set_chunk_size($this->resource, $arg1);
        }
    }
    
    function stream_write($data)
    {
        return fwrite($this->resource, $data);
    }
    
    function unlink($path)
    {
        $this->unwrap();
        if (isset($this->context)) {
            $result = unlink($path, $this->context);
        } else {
            $result = unlink($path);
        }
        $this->wrap();
        return $result;
    }

    function stream_metadata($path, $option, $value)
    {
        $this->unwrap();
        switch ($option) {
            case STREAM_META_TOUCH:
                if (empty($value)) {
                    $result = touch($path);
                } else {
                    $result = touch($path, $value[0], $value[1]);
                }
                break;
            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                $result = chown($path, $value);
                break;
            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                $result = chgrp($path, $value);
                break;
            case STREAM_META_ACCESS:
                $result = chmod($path, $value);
                break;
        }
        $this->wrap();
        return $result;
    }

    function stream_truncate($new_size)
    {
        return ftruncate($this->resource, $new_size);
    }
}
