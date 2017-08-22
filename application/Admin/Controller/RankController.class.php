<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class RankController extends AdminbaseController{
    
	protected $rank_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->rank_model = M('Rank');
	}
	
	// 后台广告列表
	public function index(){
		$ranks = $this->rank_model->where(array('status'=>1))->order('id asc')->select();
		$this->assign('ranks', $ranks);
		$this->display();
	}
	
	// 广告添加
	public function add(){
		$this->display();
	}
	
	// 广告添加提交
	public function add_post(){
		if (IS_POST) {
			$rank=I("post.");
			$result=$this->rank_model->add($rank);
			if ($result) {
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
			 
		}
	}
	
	// 广告编辑
	public function edit(){
		$id=  I("get.id",0,'intval');
		$ranks = $this->rank_model->where(array('status'=>1, 'id'=>$id))->find();
		$this->assign('ranks', $ranks);
		$this->display();
	}
	
	// 广告编辑提交
	public function edit_post(){
		if (IS_POST) {
			$rank=I("post.");
			$result=$this->rank_model->save($rank);
			if ($result!==false) {
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}
	
	// 广告删除
	public function delete(){
		$id = I("get.id",0,"intval");
		if ($this->rank_model->delete($id)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
	// 广告显示/隐藏
	public function toggle(){
		if(!empty($_POST['ids']) && isset($_GET["display"])){
			$ids = I('post.ids/a');
			if ($this->rank_model->where(array('ad_id'=>array('in',$ids)))->save(array('status'=>1))!==false) {
				$this->success("显示成功！");
			} else {
				$this->error("显示失败！");
			}
		}
		
		if(isset($_POST['ids']) && isset($_GET["hide"])){
			$ids = I('post.ids/a');
			if ($this->rank_model->where(array('ad_id'=>array('in',$ids)))->save(array('status'=>0))!==false) {
				$this->success("隐藏成功！");
			} else {
				$this->error("隐藏失败！");
			}
		}
	}
	
}