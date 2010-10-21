<?php /* Smarty version 2.6.26, created on 2010-10-20 18:04:32
         compiled from inc_head.txt */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'inc_head.txt', 5, false),)), $this); ?>
<?php utf8_headers(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php if ($this->_tpl_vars['page_title']): ?><?php echo ((is_array($_tmp=$this->_tpl_vars['page_title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
<?php else: ?>Page Title<?php endif; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="/css/dotspotting.css" />
</head>
<body>
<div id="navi_head">
<?php if ($this->_tpl_vars['cfg']['user_ok']): ?>
<a href="/account">your account</a> | <a href="/signout">sign out</a>
<?php else: ?>
<a href="/signin">sign in</a> | <a href="/signup">signup</a>
<?php endif; ?>
</div>