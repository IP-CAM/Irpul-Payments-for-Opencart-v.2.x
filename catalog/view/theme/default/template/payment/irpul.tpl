<span id="payment"></span>
<div class="well well-sm">
  	<div class="buttons">
	  <div class="right"><a id="button-confirm" class="btn btn-primary"><span><?php echo $button_confirm; ?></span></a></div>
	</div>
</div>


<script type="text/javascript"><!--
$('#button-confirm').bind('click', function() {
	$.ajax({
		type: 'GET',
		url: 'index.php?route=payment/irpul/confirm',
		dataType: 'json',
		beforeSend: function() {
			$('#button-confirm').attr('disabled', true);
			$('#payment').before('<div id="attention" class="alert alert-info"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
			$('#irpul_error').remove();
		},
		success: function(json) {
			if (json['error']) {
				$('#payment').before('<div id="irpul_error" class="alert alert-danger"><i class="fa fa-info-circle"></i> ' + json['error'] + '</div>');
				//alert(json['error']);
				$('#button-confirm').attr('disabled', false);
			}
			$('#attention').remove();
			if (json['success']) {
				$('#payment').before('<div class="alert alert-success"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_ersal; ?></div>');
				location = json['success'];
			}
		}
	});
});
//--></script>