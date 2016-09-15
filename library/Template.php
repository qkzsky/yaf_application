<?php

/**
 * Created by PhpStorm.
 * User: kuangzhiqiang
 * Date: 16/9/6
 * Time: 10:02
 */
class Template
{
    private static $_static;
    private        $left_delimiter  = '{';
    private        $right_delimiter = '}';
    private        $secret_code     = '^*31eu&sP$8#';

    static public function compile($file)
    {
        if (empty(self::$_static))
        {
            self::$_static = new static();
        }
        return self::$_static->_createFile($file);
    }

    /**
     * 生成文件
     * @param $tpl_file
     * @return string
     * @throws ErrorException
     */
    private function _createFile($tpl_file)
    {
        $tpl_real_file = realpath($tpl_file);
        if ($tpl_real_file === false)
        {
            throw new ErrorException("tpl file not found: {$tpl_file}", ErrorCode::SYS_FAILED);
        }
        $compile_dir = Yaf_Application::app()->getConfig()->application->compile;
        if (!file_exists($compile_dir))
        {
            mkdir($compile_dir, 0777, true);
        }

        // 最终生成的编译文件路径
        $compile_file_name = md5($this->secret_code . $tpl_real_file) . "_" . basename($tpl_file);
        $compile_file_path = $compile_dir . '/' . $compile_file_name;
        if (!file_exists($compile_file_path) || (filemtime($tpl_real_file) > filemtime($compile_file_path)))
        {
            $this->_compile($tpl_file, $compile_file_path);
        }
        return $compile_file_path;
    }

    /**
     * 生成编译文件
     * @param string $src_file 源文件路径
     * @param string $dest_file 目标文件路径
     */
    private function _compile($src_file, $dest_file)
    {
        $_content = file_get_contents($src_file);
        $_content = $this->parse($_content);
        file_put_contents($dest_file, $_content);
    }

    /**
     * 过滤转义字符
     * @param string $expr 前段
     * @param string $statement 后段
     * @return string
     */
    private function _stripvtags($expr, $statement)
    {
        $expr      = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    /**
     * 解析文件
     * @param string $template
     * @return mixed
     */
    function parse($template)
    {
        $template = preg_replace("/([\n\r\t]*)\<\!\-\-\s*{$this->left_delimiter}(.+?){$this->right_delimiter}\s*\-\-\>([\n\r\t]*)/is", "\\1{\\2}\\3", $template);
        // include
        $template = preg_replace("/([\n\r\t]*){$this->left_delimiter}include\s+(.+?){$this->right_delimiter}([\n\r\t]*)/is", "\\1<?php include Template::compile(\\2); ?>\\3", $template);
        // 常量
        $template = preg_replace("/{$this->left_delimiter}CONST(ANT)?\[['\"]?([a-zA-Z0-9_]+)['\"]?\]{$this->right_delimiter}/is", "<?php if(defined('\\2')){echo \\2;}?>", $template);
        // 四则运算
        $template = preg_replace("/{$this->left_delimiter}(([ \(\)\[\]'\"\$\w_\.]+)([ ]*[\+\-\*%\/][ ]*([ \(\)\[\]'\"\$\w_\.]+))+){$this->right_delimiter}/is", "<?php echo \\1;?>", $template);
        $template = preg_replace("/{$this->left_delimiter}(\\\$[a-zA-Z0-9_\[\]\-\>'\"\$\x7f-\xff]+){$this->right_delimiter}/is", "<?php if(isset(\\1)){echo \\1;}?>", $template);
        $template = preg_replace("/{$this->left_delimiter}([a-zA-Z0-9_\\\[\]\'\"\$\x7f-\xff]+(::[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)){$this->right_delimiter}/is", "<?php echo \\1;?>", $template);// static
        $template = preg_replace("/{$this->left_delimiter}(\\\$[a-zA-Z0-9_\[\]\-\>'\"\.\$\x7f-\xff]+)\s*\|\s*default:\s*([^{$this->right_delimiter}]*){$this->right_delimiter}/is", "<?php if(isset(\\1)){echo \\1;}else{echo \\2;}?>", $template);
        // 函数
        $template = preg_replace("/{$this->left_delimiter}([@&\\\$a-zA-Z0-9_:]+)\((.*?)\){$this->right_delimiter}/is", "<?php echo \\1(\\2);?>", $template);
        $template = preg_replace_callback("/([\n\r\t]*){$this->left_delimiter}elseif\s+(.+?){$this->right_delimiter}([\n\r\t]*)/is", function($matches)
        {
            return $this->_stripvtags("<?php } elseif({$matches[2]}) { ?>", '');
        }, $template);
        $template = preg_replace("/([\n\r\t]*){$this->left_delimiter}else{$this->right_delimiter}([\n\r\t]*)/is", "<?php } else { ?>", $template);

        for ($i = 0; $i <= 8; $i++)
        {
            $template = preg_replace_callback("/([\n\r\t]*){$this->left_delimiter}loop\s+(\S+)\s+(\S+){$this->right_delimiter}([\n\r\t]*)(.+?)([\n\r\t]*){$this->left_delimiter}\/loop{$this->right_delimiter}([\n\r\t]*)/is", function($matches)
            {
                return $this->_stripvtags("{$matches[1]}<?php if(isset({$matches[2]}) && is_array({$matches[2]})){ foreach({$matches[2]} as {$matches[3]}){ ?>{$matches[4]}", "{$matches[5]}{$matches[6]}<?php }}?>{$matches[7]}");
            }, $template);

            $template = preg_replace_callback("/([\n\r\t]*){$this->left_delimiter}for\s+(.+?);\s*(.+?);\s*(.+?){$this->right_delimiter}([\n\r\t]*)(.+?)([\n\r\t]*){$this->left_delimiter}\/for{$this->right_delimiter}([\n\r\t]*)/is", function($matches)
            {
                return $this->_stripvtags("{$matches[1]}<?php for({$matches[2]}; {$matches[3]}; {$matches[4]}){ ?>{$matches[5]}", "{$matches[6]}{$matches[7]}<?php }?>{$matches[8]}");
            }, $template);

            $template = preg_replace_callback("/([\n\r\t]*){$this->left_delimiter}loop\s+(\S+)\s+(\S+)\s+(\S+){$this->right_delimiter}([\n\r\t]*)(.+?)([\n\r\t]*){$this->left_delimiter}\/loop{$this->right_delimiter}([\n\r\t]*)/is", function($matches)
            {
                return $this->_stripvtags("{$matches[1]}<?php if(isset({$matches[2]}) && is_array({$matches[2]})){ foreach({$matches[2]} as {$matches[3]} => {$matches[4]}){ ?>{$matches[5]}", "{$matches[6]}{$matches[7]}<?php }}?>{$matches[8]}");
            }
                , $template);

            $template = preg_replace_callback("/([\n\r\t]*){$this->left_delimiter}if\s+(.+?){$this->right_delimiter}([\n\r\t]*?)(.+?)([\n\r\t]*){$this->left_delimiter}\/if{$this->right_delimiter}([\n\r\t]*)/is", function($matches)
            {
                return $this->_stripvtags("{$matches[1]}<?php if({$matches[2]}) { ?>{$matches[3]}", "{$matches[4]}{$matches[5]}<?php } ?>{$matches[6]}");
            }, $template);
        }
        return $template;
    }
}