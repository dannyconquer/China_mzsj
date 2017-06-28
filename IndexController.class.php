<?php

namespace Home\Controller;
use OT\DataDictionary;
use Think\Model;
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController
{

	//系统首页
	public function index()
	{

		$category = D('Category')->getTree();
		$lists = D('Document')->lists(null);
		$this->assign('category', $category);//栏目
		$this->assign('lists', $lists);//列表
		$this->assign('page', D('Document')->page);//分页
		$form = M('Mzsjxxb');
		$sql=$form->fetchSql(true)->select(); 
		if(UID){
			if($map==null)
				$map="(".substr($sql,35)."`sjyxbz`=0 ) AND (`gxbz`=1)";
			else
				$map="(".substr($sql,35)." AND `sjyxbz`=0 ) AND (`gxbz`=1)";
		}else{
		$map['sjyxbz']=0;
		$map['gxbz']=1;}
		$listcount = $form->where($map)->count();
		//var_dump($listcount);return;
		if ($listcount == 0) {
			
			$this->display('Index:index', 'UTF-8');
		}
		else {
			$order['zjxgsj']='desc';
			$list = $form->where($map)->order($order)->limit(10)->select();
			$this->assign('list', $list);
		}
		$this->display('Index:index', 'UTF-8');
	}

	public function zhindex()
	{
		$this->display('Index:indexzhcx', 'UTF-8');
	}

	public function mzsftest()
	{
		$data = I('get.');
		$index = $data['iSortCol_0'];
		$ord = $data['sSortDir_0']; //排序
		$pagenum = $data['iDisplayLength'];
		$page_start = $data['iDisplayStart'];
		$field = $data['mDataProp_' . $index]; //排序字段
		$sSearch = $data['sSearch']; //关键字
		$sql_where = "WHERE `sjzt`=1";
		/*if ($sSearch) {
			$sql_where = " WHERE name like '%" . $sSearch . "%'";
		}*/
		$user = M('Mzsjxxb');
		$map['sjzt'] = 1;
		$num = $user->where($map)->count();

		$Model = new Model();
		$sqlstring = "SELECT qsf,wzsf,nzsf,hhs,zgnzsf,sf FROM mzsj_mzsjxxb " . $sql_where . "  ORDER BY " . $field . " " . $ord . "" . " LIMIT " . $page_start . "," . $pagenum . "";
		$list = $Model->query($sqlstring, $sparse = false);
		$data = array(
			'iTotalRecords' => $num,
			'iTotalDisplayRecords' => $num,
			'aaData' => $list
		);
		$this->ajaxReturn($data);
	}

	public function datatabletest()
	{
		$data = I('get.');
		$draw = $data['draw'];
		$order_column = $data['order']['0']['column'];//那一列排序，从0开始
		//拼接排序sql
		$order_dir = " " . $data['order']['0']['dir'] . " ";//ase desc
		$orderSql = "";
		$temporderSql = "";
		$sumSqlWhere = " where ";
		$selectstring = " ";
		//搜索
		$search = $data['search']['value'];//获取前台传过来的过滤条件
		foreach ($data['form'] as $k => $v) {
			$temporderSql[$k] = " order by " . $v . $order_dir;
			$sumSqlWhere = $sumSqlWhere . $v . "||";
			$selectstring = $selectstring . $v . ",";
		}
		$sumSqlWhere = mb_substr($sumSqlWhere, 0, -2) . " LIKE '%" . $search . "%' and `sjzt`=1";
		$selectstring = mb_substr($selectstring, 0, -1) . " ";
		if (isset($order_column)) {
			$i = intval($order_column);
			$orderSql = $temporderSql[$i];
		}
		//分页
		$start = $data['start'];//从多少开始
		$length = $data['length'];//数据长度
		$limitSql = '';
		$limitFlag = isset($data['start']) && $length != -1;
		if ($limitFlag) {
			$limitSql = " LIMIT " . intval($start) . ", " . intval($length);
		}
		//处理sql
		$db = M('Mzsjxxb');
		$map['sjzt'] = 1;
		$recordsTotal = $db->where($map)->count();
		$recordsFiltered = 0;
		$Model = new Model();
		$boolsearch = strlen($search);
		if ($boolsearch > 0) {
			$sqlstring = "SELECT COUNT('id') AS num FROM " . $data['tableid'] . $sumSqlWhere;
			$list = $Model->query($sqlstring, $sparse = false);
			$recordsFiltered = intval($list['num']);
		} else {
			$recordsFiltered = $recordsTotal;
		}
		if ($boolsearch > 0) {
			$sqlstring = "SELECT " . $selectstring . " FROM " . $data['tableid'] . $sumSqlWhere . $orderSql . $limitSql;
			$list = $Model->query($sqlstring, $sparse = false);
		} else {
			$sqlstring = "SELECT " . $selectstring . " FROM " . $data['tableid'] . " WHERE `sjzt`=1" . $orderSql . $limitSql;
			$list = $Model->query($sqlstring, $sparse = false);
		}
		$data = array(
			'draw' => intval($draw),
			"recordsTotal" => intval($recordsTotal),
			"recordsFiltered" => intval($recordsFiltered),
			'data' => $list
		);
		$this->ajaxReturn($data);
	}

	public function ajaxOneDataReturn()
	{
		$db = M('Mzsjxxb');
		$condition['id'] = I('get.id');
		$data = $db->where($condition)->find();
		$this->ajaxReturn($data);
	}

	/**
	 *
	 */
	public function indexSearchReturnToDatatables()
	{
		if (IS_GET) {
			$data = I('get.');
			foreach ($data as $k=>$v){
				if($k=='keyword'){
					if(strlen($v)!=0){
					$map1['ypscsydwmc']=array('LIKE', "%" . $v . "%");
					$map1['tjz']=array('LIKE', "%" . $v . "%");
					$map1['_logic'] = 'or';
					$flag['keyword']=$v;
					$map['_complex'] = $map1;
					}
				}else if(substr($k,-3)=='_bj'){//$k比较符号的name
					
					$_k=substr($k,0,-3);//$_k字段的name
					if(strlen($data[$_k])!=0){//data[$_k]字段的值
						$map[$_k]=array( $v, $data[$_k]);//$v 比较符号的值,数据库中的字段值与$data中的值做比较。
						$map['_logic'] = 'and';
						$flag[$_k]=$data[$_k];
						$flag[$k]=$v;//$v 比较符号的值
					} else {
						$flag[$k]='GT';
					}
				}
			}
		} else {
			$map = 1;
		}
		$form = M('Mzsjxxb');
		$sql=$form->where($map)->fetchSql(true)->select(); 
		if(UID){
			if($map==null)
				$map="(".substr($sql,35)."`sjyxbz`=0 ) AND (`gxbz`=1 OR ownerid =" . UID.")";
			else
				$map="(".substr($sql,35)." AND `sjyxbz`=0 ) AND (`gxbz`=1 OR ownerid =" . UID.")";
		}else{
		$map['sjyxbz']=0;
		$map['gxbz']=1;}
		$listcount = $form->where($map)->count();
		if ($listcount == 0) {
			$show = '<div class="productcontenterror"><span>没有数据</span></div>';
			$this->assign('flag', $flag);
			$this->assign('page', $show);
			$this->display('Index:indexzhcx', 'UTF-8');
		} else {
			$page = new  \Think\Page($listcount, 10);
			$page->setConfig('first', '首页');
			$page->setConfig('last', '尾页');
			$page->setConfig('prev', '上一页');
			$page->setConfig('next', '下一页');
			$page->setConfig('theme', '%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
			cookie('nowpage', $page->nowpage());
			$show = $page->show();
			$list = $form->where($map)->limit($page->firstRow . ',' . $page->listRows)->select();
			$this->assign('list', $list);
			$this->assign('flag', $flag);
			$this->assign('page', $show);
			$this->display('Index:indexzhcx', 'UTF-8');
		}

	}
	public function shouyedata()
	{
		$form = M('Mzsjxxb');
		
		$sql=$form->fetchSql(true)->select(); 
		if(UID){
			if($map==null)
				$map="(".substr($sql,35)."`sjyxbz`=0 ) AND (`gxbz`=1)";
			else
				$map="(".substr($sql,35)." AND `sjyxbz`=0 ) AND (`gxbz`=1)";
		}else{
		$map['sjyxbz']=0;
		$map['gxbz']=1;}
		$listcount = $form->where($map)->count();
		if ($listcount == 0) {
			$show = '<div class="productcontenterror"><span>没有数据</span></div>';
			//$this->assign('flag', $flag);
			//$this->assign('page', $show);
			$this->display('Index:index', 'UTF-8');
		} else {
			$page = new  \Think\Page($listcount, 10);
			$page->setConfig('first', '首页');
			$page->setConfig('last', '尾页');
			$page->setConfig('prev', '上一页');
			$page->setConfig('next', '下一页');
			$page->setConfig('theme', '%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
			cookie('nowpage', $page->nowpage());
			//$show = $page->show();
			$order['time']='desc';
			$list = $form->where($map)->order($order)->limit(6)->select();
			
			$this->assign('list', $list);
			//$this->assign('flag', $flag);
			//$this->assign('page', $show);
			$this->display('Index:index', 'UTF-8');
		}

	}

	public function verify() {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }
}