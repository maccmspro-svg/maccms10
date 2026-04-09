<?php
/**
 * 中文二级目录支持：自动检测安装目录与 PATH_INFO
 * 不依赖 config/ThinkPHP，仅使用 $_SERVER，入口文件最先加载
 */
if (!function_exists('mac_calc_install_dir')) {
    /**
     * 从当前请求自动计算安装目录（支持中文路径与 URL 编码）
     * 返回以 / 结尾的路径，根目录为 /
     */
    function mac_calc_install_dir()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null || $path === '') {
            $path = '/';
        }
        $path = '/' . trim(str_replace('\\', '/', $path), '/');
        if ($path !== '/') {
            $path .= '/';
        }
        $script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php');
        $pos = strrpos($path, $script);
        if ($pos !== false) {
            $dir = substr($path, 0, $pos);
        } else {
            $dir = '/';
        }
        $dir = '/' . trim($dir, '/');
        if ($dir !== '/') {
            $dir .= '/';
        }
        return $dir;
    }
}

if (!function_exists('mac_normalize_path_info')) {
    /**
     * 根据 REQUEST_URI 正确设置 PATH_INFO，兼容中文二级目录（避免 SCRIPT_NAME 与 REQUEST_URI 编码不一致）
     */
    function mac_normalize_path_info()
    {
        if (php_sapi_name() === 'cli') {
            return;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null || $path === '') {
            return;
        }
        $path = '/' . trim(str_replace('\\', '/', $path), '/');
        $script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php');
        $pos = strrpos($path, $script);
        if ($pos !== false) {
            $pathInfo = substr($path, $pos + strlen($script));
            $pathInfo = trim($pathInfo, '/');
            $_SERVER['PATH_INFO'] = $pathInfo === '' ? '/' : '/' . $pathInfo;
        }
    }
}
