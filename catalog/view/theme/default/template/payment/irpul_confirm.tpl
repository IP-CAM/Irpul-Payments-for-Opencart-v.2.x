<?php echo $header; ?>
<div class="container">
  <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
    <?php } ?>
  </ul>
  <div class="row"><?php echo $column_left; ?>
    <?php if ($column_left && $column_right) { ?>
    <?php $class = 'col-sm-6'; ?>
    <?php } elseif ($column_left || $column_right) { ?>
    <?php $class = 'col-sm-9'; ?>
    <?php } else { ?>
    <?php $class = 'col-sm-12'; ?>
    <?php } ?>
    <div id="content" class="<?php echo $class; ?>"><?php echo $content_top; ?>
      <h1><?php echo $heading_title; ?></h1>
		<?php if ($error_warning) { ?><div class="alert alert-warning"><?php echo $error_warning; ?></div><?php } else { ?>
		
		<div class="table-responsive">
			<div class="alert alert-success"><?php echo $text_message; ?></div>
			<table class="table table-bordered table-hover">
				<tr>
					<td width="30%">شناسه تراکنش</td>
					<td><?php echo isset($trans_id) ? $trans_id : ''; ?></td>
				</tr>
				<tr>
					<td width="30%">شماره پیگیری</td>
					<td><?php echo isset($refcode) ? $refcode : ''; ?></td>
				</tr>
			</table>
		</div>
		<?php } ?>
		<div class="buttons">
			<div class="pull-right"><a href="<?php echo $continue; ?>" class="btn btn-primary"><?php echo $btn_text; ?></a></div>
		</div>
	</div>
<?php echo $content_bottom; ?></div>
    <?php echo $column_right; ?></div>
</div>
<?php echo $footer; ?>