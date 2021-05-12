<?php
namespace app\admin\controller;

/**
 */
class Filelog extends AdminBase
{
    /**
     *
     */
    public function lists()
    {
        $where = [];
        $start_date = strtotime(date("Y-m-d",time())) - 3600*24*7;
        $end_date = strtotime(date("Y-m-d",time()+ 3600*24))-1;
        if(!empty($this->param['start_date'])){
            $start_date = strtotime($this->param['start_date']);
        }
        if(!empty($this->param['end_date'])){
            $end_date = strtotime($this->param['end_date']) + 3600*24-1;
        }

        $where['fl.create_time'] = ['between', [$start_date,$end_date]];
        $list = $this->logicFilelog->getFilelogList($where,'','fl.id desc','20');
        $this->assign('start_date',date("Y-m-d",$start_date));
        $this->assign('end_date',date("Y-m-d",$end_date));
        $this->assign('list',$list);
        return $this->fetch('list');
    }

    public function logDetail()
    {
        $where = [];
        $where['id'] = $this->param['log_id'];
        $list = $this->logicFilelog->getFilelogInfo($where);
        if($list){
            $list['error_txt'] = str_replace("|","<br/>",$list['error_txt']);
        }
        exit(json_encode($list));
    }
}
