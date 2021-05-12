<?php
namespace addon\region\controller;
use app\common\controller\AddonBase;

class Index extends AddonBase
{

    /**
     * 获取选项信息
     */
    public function getOptions()
    {

        $where['upid']      = input('upid', DATA_DISABLE);
        $where['level']     = input('level', DATA_NORMAL);

        $select_id = input('select_id', DATA_DISABLE);

        $list = $this->logicIndex->getRegionList($where);

        switch ($where['level'])
        {
            case 1: $default_option_text = "---请选择省份---"; break;
            case 2: $default_option_text = "---请选择城市---"; break;
            case 3: $default_option_text = "---请选择区县---"; break;
            default: $this->error('省市县 level 不存在');
        }

        $data = $this->logicIndex->combineOptions($select_id, $list, $default_option_text);

        return $this->result($data);
    }
}