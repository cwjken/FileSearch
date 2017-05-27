<?php
/**
 * 设计思路：
 * 把文件重新格式化，进行二分查找
 * 调取方法：
 *     $m = new FileSearch("test.log", 200);
 *     echo $m->search(95500736); //在文件中搜索
 * 说明：第一次使用会很慢，因为要生成缓存文件
 */
class FileSearch
{
    private $filename;   //源文件名
    private $maxLength;  //源文件中单行的最大长度(按字节算)
    private $sorted;     //源文件是否已经排好序
    private $formatFile; //重新格式化存储的文件名
    
    /**
     * 初始化
     * @param  string $filename  需要检索的文件
     * @param  int $maxLength    单行的最大长度
     * @param  int $sorted       源文件是否已经排好序
     * @param  int $forceReForm  是否强制重新生成索引文件
     * @return [type]            [description]
     */
    public function __construct($filename, $maxLength, $sorted = 1, $forceReForm = 0)
    {
        $this->filename = $filename;
        $this->maxLength = $maxLength;
        $this->sorted = $sorted;
        $this->formatFile = dirname(__FILE__)."/filecache/".md5($this->filename);

        if ($forceReForm || !file_exists($this->formatFile)) {
            $this->formatFile();
        }
    }
    /**
     * 格式化文件
     * @return [type] [description]
     */
    private function formatFile()
    {
        if ($this->sorted == 0) {
            //对源文件排序
        }
        //读源文件，写入到新的索引文件
        $readfd = fopen($this->filename, 'rb');
        $writefd = fopen($this->formatFile.'_tmp', 'wb+');
        if ($readfd === false || $writefd === false) {
            return false;
        }
        echo "\n start reformat file $this->filename ..<br />";
        while (!feof($readfd)) {
            $line = fgets($readfd, 8192);
            fwrite($writefd, pack("a".$this->maxLength, $line));     //生成一个每行有数量相等字节的二进制文件
        }
        echo "\n reformat ok\n";
        fclose($readfd);
        fclose($writefd);
        rename($this->formatFile.'_tmp', $this->formatFile);
    }


    /**
     * 在索引文件中进行二分查找
     * @param  int $id    进行二分查找的id
     * @return [type]     [description]
     */
    public function search($key)
    {
        $filesize = filesize($this->formatFile);                   //文件大小多少字节
        $fd = fopen($this->formatFile, "rb");
        $left = 0; //行号
        $right = ($filesize / $this->maxLength) - 1;               //总共有多少行，总字节数除以每一行字节数，因为从0开始所以减1

        while ($left <= $right) {                                   
            $middle = intval(($right + $left)/2);                  //取出中间行数（整数）
            fseek($fd, ($middle) * $this->maxLength);              //把指针移动到中那行
            $info = unpack("a*", fread($fd, $this->maxLength))['1'];//解压缩二进制文件

            $lineinfo = explode("\t", $info, 2);                     //获取序号和值
            if ($lineinfo['0'] > $key) {
                $right = $middle - 1;
            } elseif ($lineinfo['0'] < $key) {
                $left = $middle + 1;
            } else {
                return $lineinfo['1'];
            }
        }
        return false;
    }
}

