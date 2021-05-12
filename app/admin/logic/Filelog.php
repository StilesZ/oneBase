<?php

namespace app\admin\logic;

class Filelog extends AdminBase
{
    public function getFilelogList($where = [], $field = true, $order = '', $paginate = 0)
    {
        $this->modelFileLog->alias('fl');
        $join = [
                    [SYS_DB_PREFIX . 'member m', 'fl.op_id = m.id', 'LEFT'],
                ];
        $this->modelFileLog->join = $join;
        $field = "fl.*,m.username";
        return $this->modelFileLog->getList($where, $field, $order, $paginate);
    }

    public function getFilelogInfo($where = [], $field = true)
    {
        $info = $this->modelFileLog->getInfo($where, $field);

        return $info;
    }
}
