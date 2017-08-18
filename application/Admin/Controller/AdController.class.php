<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AdController extends AdminbaseController{
    
	protected $ad_model;
	protected $terms_model;
	protected $term_relationships_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->ad_model = D("Common/Ad");
		$this->terms_model = D("Portal/Terms");
		$this->term_relationships_model = M('ad_relationships');
	}
	
	// 后台广告列表
	public function index(){
		$this->_lists(array("ad_status"=>array('neq',3)));
		$this->_getTree();
		$this->display();
	}
	
	// 广告添加
	public function add(){
		
		$terms = $this->terms_model->order(array("listorder"=>"asc"))->select();
		$term_id = I("get.term",0,'intval');
		$this->_getTermTree();
		$term=$this->terms_model->where(array('term_id'=>$term_id))->find();
		$this->assign("term",$term);
		$this->assign("terms",$terms);

		$this->display();
	}
	
	// 广告添加提交
	public function add_post(){
		if (IS_POST) {
			if(empty($_POST['term'])){
				$this->error("请至少选择一个分类！");
			}
			
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			 
			$article=I("post.post");
			$article['smeta']=json_encode($_POST['smeta']);
			$article['ad_content']=htmlspecialchars_decode($article['ad_content']);
			$result=$this->ad_model->add($article);
			if ($result) {
				foreach ($_POST['term'] as $mterm_id){
					$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$result));
				}
				
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
			 
		}
	}
	
	// 广告编辑
	public function edit(){
		$id=  I("get.id",0,'intval');
		
		$term_relationship = M('AdRelationships')->where(array("object_id"=>$id,"status"=>1))->getField("term_id",true);
		$this->_getTermTree($term_relationship);
		$terms=$this->terms_model->select();
		$post=$this->ad_model->alias("a")
		->join(C('DB_PREFIX')."ad_relationships b ON a.ad_id = b.object_id")->where("ad_id=$id")->find();
		$this->assign("ad",$post);
		$this->assign("smeta",json_decode($post['smeta'],true));
		$this->assign("terms",$terms);
		$this->assign("term",$term_relationship);
		$this->display();
	}
	
	// 广告编辑提交
	public function edit_post(){
		if (IS_POST) {
			if(empty($_POST['term'])){
				$this->error("请至少选择一个分类！");
			}
			$post_id=intval($_POST['post']['ad_id']);
			
			$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>array("not in",implode(",", $_POST['term']))))->delete();
			foreach ($_POST['term'] as $mterm_id){
				$find_term_relationship=$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->count();
				if(empty($find_term_relationship)){
					$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$post_id));
				}else{
					$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->save(array("status"=>1));
				}
			}
			
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$article=I("post.post");
			$article['smeta']=json_encode($_POST['smeta']);
			$article['ad_content']=htmlspecialchars_decode($article['ad_content']);
			$result=$this->ad_model->save($article);
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
		if ($this->ad_model->delete($id)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
	// 广告显示/隐藏
	public function toggle(){
		if(!empty($_POST['ids']) && isset($_GET["display"])){
			$ids = I('post.ids/a');
			if ($this->ad_model->where(array('ad_id'=>array('in',$ids)))->save(array('status'=>1))!==false) {
				$this->success("显示成功！");
			} else {
				$this->error("显示失败！");
			}
		}
		
		if(isset($_POST['ids']) && isset($_GET["hide"])){
			$ids = I('post.ids/a');
			if ($this->ad_model->where(array('ad_id'=>array('in',$ids)))->save(array('status'=>0))!==false) {
				$this->success("隐藏成功！");
			} else {
				$this->error("隐藏失败！");
			}
		}
	}

	/**
	 * 文章列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 */
	private function _lists($where=array()){
		$term_id=I('request.term',0,'intval');
		
		if(!empty($term_id)){
		    $where['b.term_id']=$term_id;
			$term=$this->terms_model->where(array('term_id'=>$term_id))->find();
			$this->assign("term",$term);
		}
		
		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['add_date']=array(
		        array('EGT',$start_time)
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['add_date'])){
		        $where['add_date']=array();
		    }
		    array_push($where['add_date'], array('ELT',$end_time));
		}
		
		$keyword=I('request.keyword');
		if(!empty($keyword)){
		    $where['post_title']=array('like',"%$keyword%");
		}
			
		$this->ad_model
		->alias("a")
		->where($where)
		->join(C('DB_PREFIX')."ad_relationships b ON a.ad_id = b.object_id")
		->group('b.object_id');
		
		$count=$this->ad_model->count();
			
		$page = $this->page($count, 20);
			
		$this->ad_model
		->alias("a")
		->join(C('DB_PREFIX')."ad_relationships b ON a.ad_id = b.object_id")
		->where($where)
		->group('b.object_id')
		->limit($page->firstRow , $page->listRows)
		->order("a.add_date DESC");
		
		$posts=$this->ad_model->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("ads",$posts);
	}
	
	// 获取文章分类树结构 select 形式
	private function _getTree(){
		$term_id=empty($_REQUEST['term'])?0:intval($_REQUEST['term']);
		$result = $this->terms_model->where(array("parent"=>0))->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
			$r['visit'] = "<a href='#'>访问</a>";
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			$r['selected']=$term_id==$r['term_id']?"selected":"";
			$array[] = $r;
		}
		
		$tree->init($array);
		$str="<option value='\$id' \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		$this->assign("taxonomys", $taxonomys);
	}
	// 获取文章分类树结构 
	private function _getTermTree($term=array()){
		
		$result = $this->terms_model->where(array('parent'=>0))->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
			$r['visit'] = "<a href='#'>访问</a>";
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			$r['selected']=in_array($r['term_id'], $term)?"selected":"";
			$r['checked'] =in_array($r['term_id'], $term)?"checked":"";
			$array[] = $r;
		}
		
		$tree->init($array);
		$str="<option value='\$id' \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		$this->assign("taxonomys", $taxonomys);
	}
	
	
}