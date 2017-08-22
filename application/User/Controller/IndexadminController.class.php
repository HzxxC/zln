<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {
    
    // 后台本站用户列表
    public function index(){
        $where=array();
        $request=I('request.');
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }
        
        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['user_login']  = array('like', "%$keyword%");
            $keyword_complex['user_nicename']  = array('like',"%$keyword%");
            $keyword_complex['user_email']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }

        $where['user_type'] = 3;
        
    	$users_model=M("Users");
    	
    	$count=$users_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $users_model
    	->where($where)
    	->order("create_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	
    	$this->assign('list', $list);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }

    public function add() {
        $this->display(":add");
    }

    public function add_post() {
        $this->users_model = M('Users');
        if (IS_POST) {
            $data=I("post.");
            if ($this->users_model->create($data)!==false) {
                $result=$this->users_model->add();
                if ($result!==false) {
                    $data['id']=$result;
                    $data['create_time'] = date('y-m-d H:i:s', time());
                    $this->users_model->save($data);
                    $this->success("添加成功！", U("indexadmin/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->users_model->getError());
            }
        }
    }
    public function edit() {

        $this->users_model = M('Users');
    
        $id= I("get.id",0,'intval');
        $userinfo=$this->users_model->where(array('id'=>$id))->find();
    
        $this->assign($userinfo);
        $this->display(":edit");
    }

    public function edit_post() {
        $this->users_model = M('Users');
        if (IS_POST) {
            $data=I("post.");
            if ($this->users_model->create($data)!==false) {
                if ($this->users_model->save() !==false) {
                    $this->success("添加成功！", U("indexadmin/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->users_model->getError());
            }
        }
    }
    
    // 后台本站用户禁用
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',0);
    		if ($result) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败,会员不存在,或者是管理员！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    // 后台本站用户启用
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
}
