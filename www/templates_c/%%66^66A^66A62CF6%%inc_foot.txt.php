<?php /* Smarty version 2.6.26, created on 2010-10-20 18:19:22
         compiled from inc_foot.txt */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'timings', 'inc_foot.txt', 4, false),)), $this); ?>

<?php if ($_GET['debug']): ?>
<div style="padding: 2em;">
	<?php echo smarty_timings(array(), $this);?>

</div>
<?php endif; ?>

</body>
</html>