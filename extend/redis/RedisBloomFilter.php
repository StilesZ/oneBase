<?php


namespace redis;


class RedisBloomFilter
{

    protected $numApprox;// 预计元素数量
    protected $fpp;// 误差率
    private $length;// bitmap长度
    protected $numHash;// hash函数数量

    protected $bitArray = [];

    public function __construct($numApprox, $fpp)
    {
        $this->numApprox = $numApprox;
        $this->fpp = $fpp;
        //数组长度
        $this->length = (int)(-$numApprox * log($fpp)) / pow(log(2), 2);
        //数组长度
        $this->numHash = max(1, $this->length / $numApprox * log(2));

        $this->bitArray = array_pad([], $this->length, false);
    }

    /**
     * 计算hash值映射到bitmap
     * @parama element 元素值
     * @return int bit下标
     */
    public function getIndices($element)
    {
        $indices = [];
        for ($i = 0; $i < $this->numHash; $i++) {
            $index = crc32($element . $i);
            $index = $index % $this->length;
            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * 添加元素
     */
    public function addItem($element)
    {
        $indices = $this->getIndices($element);

        foreach ($indices as $index) {
            $this->bitArray[$index] = true;
        }
    }

    /**
     * 检查元素是否存在
     */
    public function mightExist($element): bool
    {
        $indices = $this->getIndices($element);

        foreach ($indices as $index) {
            if (!$this->bitArray[$index]) {
                return false;
            }
        }

        return true;
    }
}