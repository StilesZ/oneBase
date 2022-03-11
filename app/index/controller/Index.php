<?php
// +---------------------------------------------------------------------+
// | OneBase    | [ WE CAN DO IT JUST THINK ]                            |
// +---------------------------------------------------------------------+
// | Licensed   | http://www.apache.org/licenses/LICENSE-2.0 )           |
// +---------------------------------------------------------------------+
// | Author     | Bigotry <3162875@qq.com>                               |
// +---------------------------------------------------------------------+
// | Repository | https://gitee.com/Bigotry/OneBase                      |
// +---------------------------------------------------------------------+

namespace app\index\controller;

use think\queue\Queue;

/**
 * 前端首页控制器
 */
class Index extends IndexBase
{
    
    // 首页
    public function index($cid = 0)
    {
//        $this->redirect('admin.php/login/login');
        exit('维护中');
//        $where = [];
//
//        !empty((int)$cid) && $where['a.category_id'] = $cid;
//
//        $this->assign('article_list', $this->logicArticle->getArticleList($where, 'a.*,m.nickname,c.name as category_name', 'a.create_time desc'));
//
//        $this->assign('category_list', $this->logicArticle->getArticleCategoryList([], true, 'create_time asc', false));
//
//        return $this->fetch('index');
    }
    
    // 详情
    public function details($id = 0)
    {
        
        $where = [];
        
        !empty((int)$id) && $where['a.id'] = $id;
        
        $data = $this->logicArticle->getArticleInfo($where);
        
        $this->assign('article_info', $data);
        
        $this->assign('category_list', $this->logicArticle->getArticleCategoryList([], true, 'create_time asc', false));
        
        return $this->fetch('details');
    }

    // 将任务加入队列
    public function push()
    {

        $job_data                       = [];
        $job_data["member_id"]          = time();
        $job_data["to_member_id"]       = time();
        $job_data["params"]             = ['xx' => 'cc', 'vv' => 'bb'];

        // 立即执行 job处理队列数据的类路径
//        $is_pushed = Queue::push("app\queue\controller\Test", $job_data, 'test_job_queue');
        // 延时场景 秒
        $is_pushed = Queue::later(10,"app\queue\controller\Test", $job_data, 'test_job_queue');

        if($is_pushed !== false ) {

            echo date("Y-m-d H:i:s")." a new job is pushed to the message queue";
        } else {

            echo date("Y-m-d H:i:s")." a new job pushed fail";
        }
    }
}
