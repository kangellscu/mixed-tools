<?php
/*
 * $Id: install.php 15 2011-08-02 16:16:34Z leikou@163.com $
 */

/**
 * 目录文件拷贝工具，适用于代码发布，例如从开发目录发布到测试目录，方便开发
 */
class SourceFilter extends RecursiveFilterIterator 
{
    private $_valid_extensions;
    private $exclude_folders = array('.svn');


    public function __construct($iterator, $valid_extensions)
    {
        parent::__construct($iterator);

        $this->_valid_extensions = $valid_extensions;
    }


    public function accept()
    {
        $current = $this->getInnerIterator()->current();
        $file_name = $current->getFilename();

        if($current->isDir())
        {
            if(in_array($file_name, $this->exclude_folders))
            {
                return FALSE;
            }

            return TRUE;
        }

        if( ! $current->isFile())
        {
            return FALSE;
        }

        if(method_exists($current, 'getExtension'))
        {
            $extension = $current->getExtension();
        }
        else
        {
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        }

        return in_array($extension, $this->_valid_extensions);
    }


    public function getChildren()
    {
        return new self(
            $this->getInnerIterator()->getChildren(),
            $this->_valid_extensions
        );
    }
}



/**
 * Copy source code to target directory
 *
 * example:
 *      $install = new Install(
 *          'D:/copywrite',
 *          'D:/project',
 *          array('php', 'html', 'js')
 *      );
 *
 *      $install->run();
 */
class Install {
    private $_source_path;
    private $_target_path;
    private $_valid_extensions;


    /**
     * @param string $source_path must be absolute path and dir
     * @param string $target_path must be absolute path and dir
     * @param array $valid_extensions only file with extension 
     *      in this array can be copy
     */
    public function __construct(
        $source_path, 
        $target_path,
        $valid_extensions
    )
    {
        $this->_source_path = $this->_standard_path($source_path);
        $this->_target_path = $this->_standard_path($target_path);
        $this->_valid_extensions = is_array($valid_extensions) ? 
            $valid_extensions : array($valid_extensions);
    }


    /**
     * Only one metheod need be invoke
     */
    public function run()
    {
        if( ! $this->_path_valid()) {
            die(
                'source path and target path must be absolute '.
                'path and dir'
            );
        }

        $iterator = $this->_get_iterator();

        foreach($iterator as $file_name => $splfileinfo)
        {
            // Copy process
            $dest_name = $this->_get_target_path($file_name);
            if($splfileinfo->isDir()) {
                if( ! is_dir($dest_name))
                {
                    mkdir($dest_name);
                }
            }
            else
            {
                copy($file_name, $dest_name);
            }
        }
    }


    private function _get_iterator()
    {
        static $iterator = NULL;
        if($iterator === NULL)
        {
            $iterator = new RecursiveIteratorIterator(
                new SourceFilter(
                    new RecursiveDirectoryIterator($this->_source_path),
                    $this->_valid_extensions
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        }
        
        return $iterator;
    }


    private function _standard_path($path)
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }


    private function _get_target_path($file_path) {
        return str_replace(
            $this->_source_path, 
            $this->_target_path,
            $this->_standard_path($file_path) 
        );
    }


    private function _path_valid()
    {
        $patthern = '/^(\w:|\/).+/';
        $is_absolute = preg_match(
            $patthern,
            $this->_source_path
        ) && preg_match(
            $patthern,
            $this->_target_path
        );        

        $is_dir = is_dir($this->_source_path) && is_dir($this->_target_path);
        return $is_absolute && $is_dir;
    }
}


function is_argument_valid($argc, $source_path, $target_path, $usage) {
    if ($argc !== 3) {
        return $usage;
    }

    if ( ! is_dir($source_path) || ! is_dir($target_path)) {
        return $source_path . ' or ' . $target_path . 'must be folder'; 
    }

    return null;
}


/************************ Entrance ***************************/

$USAGE = <<<USAGE
    Usage: {$argv[0]} source_path target_path\n 
USAGE;

$source_path = $argv[1];
$target_path = $argv[2];
$err_msg = is_argument_valid($argc, $source_path, $target_path, $USAGE);
if ($err_msg) {
    die($err_msg);
}

$valid_extensions = array('php', 'twig', 'xml', 'yml', 'css', 'jpg', 'jpeg', 'js', 'png', 'gif', 'xsd');
$obj = new Install($source_path, $target_path, $valid_extensions);
$obj->run();

/* End of file: install.php */
