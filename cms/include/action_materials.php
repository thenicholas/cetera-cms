<?php
namespace Cetera;
/**
 * Cetera CMS 3 
 * 
 * AJAX-backend действия с материалами
 *
 * @package CeteraCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
  
include_once('common_bo.php');

$res = array(
    'success' => false,
    'errors'  => array()
);


$action = $_REQUEST['action'];
$sel = $_REQUEST['sel'];
$id = (int)$_REQUEST['id'];
$type = $_REQUEST['type'];

if ($id) 
{
	$catalog = Catalog::getById($id);
	$objectDefinition = $catalog->materialsObjectDefinition;
} 
elseif ($type)
{
	if ($type && !(int)$type)
	{
		$objectDefinition = ObjectDefinition::findByAlias($type);
	}
	else
	{
		$objectDefinition = ObjectDefinition::findById($type);
	}	
}

if ($action == 'lock') {  
    $m = Material::getById((int)$_REQUEST['mat_id'], (int)$_REQUEST['type']);
    $m->lock($user->id);
    $res['success'] = true;
}

if ($action == 'clear_lock') {
    if ((int)$_REQUEST['mat_id']) {
        $m = Material::getById((int)$_REQUEST['mat_id'], (int)$_REQUEST['type']);
        $m->unlock();
    }
    $res['success'] = true;
}

if ($_REQUEST['action'] == 'get_path_info') {
    try {
        $m = Material::getById((int)$_REQUEST['mat_id'], new ObjectDefinition($_REQUEST['type']));
        if ($m->catalog && !$m->catalog->isRoot()) $res['displayPath'] = $m->catalog->getPath()->implode().' / ';
		$res['displayPath'] .= $m->name;
    } catch (\Exception $e) {
        $res['displayPath'] = '';    
    }
	$res['success'] = true;
}

if ($action == 'permissions') {
    
    if ($catalog->isLink())
        $res['link'] = $catalog->prototype->treePath;
    
    $right[0] = $user->allowCat(PERM_CAT_OWN_MAT, $id); // Pабота со своими материалами
    $right[1] = $user->allowCat(PERM_CAT_ALL_MAT, $id); // Работа с материалами других авторов
    $right[2] = $user->allowCat(PERM_CAT_MAT_PUB, $id); // Публикация материалов

    $right[3] = '';
	
	$f = $application->getConn()->fetchArray('SELECT preview, typ FROM dir_data WHERE id=?',array((int)$id));
    if ($f)
        list($right[3], $right[4]) = $f;
    
    if ($right[3]) $right[3] = trim($right[3],'/').'/';
    $right[3] = $catalog->url.$right[3];

    if ($r) {
        $res['success'] = true;
        $res['right']   = $right;
    }
}

if ($action == 'mark_del' && is_array($sel)) {
  
    $table = $application->getConn()->fetchColumn('SELECT alias FROM types WHERE id=?',array($_POST['mat_type']),0);
	$application->getConn()->executeQuery("update $table set type=type|".MATH_DELETED." where id IN (".implode(',',$sel).")");
	$res['success'] = true;

}

if ($action == 'delete' && is_array($sel)) {
  
	foreach ($sel as $val)
	{
	   $m = Material::getById($val, $objectDefinition);
	   $application->eventLog(EVENT_MATH_DELETE, $m->getBoUrl());
       $m->delete();
    }
	$res['success'] = true;

}

if (($action == 'up' || $action == 'down' || $action == 'pub' || $action == 'unpub' || $action == 'move' || $action == 'copy') && is_array($sel)) {

    if ($_POST['math_subs'])
      $cats = 'A.idcat IN ('.implode(',',$catalog->subs).')';
      else $cats = "A.idcat=$id";

    $where = '';
	if ($action == 'up') {
		$catalog->fixMaterialTags();
		$r = $application->getConn()->query("select id from ".$objectDefinition->table." where id in (".implode(',',$sel).") order by tag");
		$sel = array();
		while($f = $r->fetch()) $sel[] = $f['id'];
	}
	if ($action == 'down') {
		$catalog->fixMaterialTags();
		$r = $application->getConn()->query("select id from ".$objectDefinition->table." where id in (".implode(',',$sel).") order by tag desc");
		$sel = array();
		while($f = $r->fetch()) $sel[] = $f['id'];
	}
	
    $handler = $application->getConn()->fetchColumn("select handler from types where alias = ?",array($objectDefinition->table));
    if ($handler) {
        if (substr($handler, -4) != '.php') $handler .= '.php';
        if ($handler && file_exists(PLUGIN_MATH_DIR.'/'.$handler)) 
            include(PLUGIN_MATH_DIR.'/'.$handler);
    }
    
    // backward compability
    $math = $objectDefinition->table;
	
    foreach($sel as $val) {
	  
	  $id2 = $val;

	  if ($action == 'copy') {
          $m = Material::getById($val, $objectDefinition);
          $mid = $m->copy($_POST['cat']);
          $new = Material::getById($mid, $objectDefinition);
          $application->eventLog(EVENT_MATH_CREATE, $new->getBoUrl());
      }

	  if ($where == '') 
		  $where="id=$val"; else $where .= " or id=$val";

	  if ($action == 'up') {
          $tag = $application->getConn()->fetchColumn("select tag from ".$objectDefinition->table." where id=?",array($val),0);

  		  $f = $application->getConn()->fetchArray("select A.tag,A.id from ".$objectDefinition->table." A where A.tag<$tag and($cats) order by A.tag desc limit 0,1");
  		  if ($f) {
    	    $application->getConn()->executeQuery("update ".$objectDefinition->table." set tag=$f[0] where id=$val");
			$application->getConn()->executeQuery("update ".$objectDefinition->table." set tag=$tag where id=$f[1]");
  		  }
	  }
	  if ($action == 'down') {
          $tag = $application->getConn()->fetchColumn("select tag,idcat from ".$objectDefinition->table." where id=?",array($val),0);
  		  $f = $application->getConn()->fetchArray("select A.tag,A.id from ".$objectDefinition->table." A where A.tag>$tag and($cats) order by A.tag limit 0,1");
  		  if ($f) {
    	    $application->getConn()->executeQuery("update ".$objectDefinition->table." set tag=$f[0] where id=$val");
			$application->getConn()->executeQuery("update ".$objectDefinition->table." set tag=$tag where id=$f[1]");
  		  }
		}
		
	    $tpl = new Cache\Tag\Material($objectDefinition->table,$val);
        $tpl->clean();
        
	    $tpl = new Cache\Tag\Material($objectDefinition->table,0);
        $tpl->clean();
    }
    
	if ($action == 'pub') {
	
	   $stat = "update ".$objectDefinition->table." set type=type | ".MATH_PUBLISHED;
	  
	}
	if ($action == 'unpub') {
	
	    $not_publish_bit = ~ MATH_PUBLISHED;
	    $stat = "update ".$objectDefinition->table." set type=type & $not_publish_bit";

	}
	if ($action == 'move') {
	
	    $tt = 1 + $application->getConn()->fetchColumn("SELECT MAX(tag) FROM ".$objectDefinition->table." WHERE idcat=?",array($_POST['cat']),0);
	    $stat = "update ".$objectDefinition->table." set idcat=".$_POST['cat'].", tag=$tt";

    }
    if ($stat) $application->getConn()->executeQuery("$stat where ($where)");
    
    if ($action == 'move' || $action == 'unpub' || $action == 'pub') {
    
        if ($action == 'move') $code = EVENT_MATH_EDIT;
          elseif ($action == 'pub') $code = EVENT_MATH_PUB;
            elseif ($action == 'unpub') $code = EVENT_MATH_UNPUB;
        
    	  foreach ($sel as $val) {
    	      $m = Material::getById((int)$val, $objectDefinition);
    	      $application->eventLog($code, $m->getBoUrl());
        	  if ($action == 'pub' && function_exists('on_publish')) on_publish();
        	  if ($action == 'unpub' && function_exists('on_unpublish')) on_unpublish();
        }
        
    }
    
    $res['success'] = true;
}


echo json_encode($res);
