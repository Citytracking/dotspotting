<?php /* Smarty version 2.6.26, created on 2010-10-20 18:19:22
         compiled from page_account.txt */ ?>
<?php $this->assign('page_title', 'Your account'); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "inc_head.txt", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<h1>Your account</h1>

<?php if ($_GET['password']): ?><p class="message">Your password has been updated.</p><?php endif; ?>

<ul>
	<li><a href="/account/password/">Change your password</a></li>
	<li><a href="/account/delete/">Delete your account</a></li>
</ul>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "inc_foot.txt", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>