var step = 1;
var regions = ["Perú", "Argentina", "Paypal", "Chile"];
var price_peru = {"Puntos de Legado":0.004};
var price_argentina = {"Puntos de Legado":0.1};
var price_paypal = {"Puntos de Legado":0.001};
var price_chile = {"Puntos de Legado":1.0};
var base_url = '';
var url_tems = base_url+'/terms';

function loadInformation(region) {
	var el_class = '.zpuMoneyRegionClass a';
	var el = $(el_class);

	if(region == 0 && !el.hasClass('zpuRegionDisabled0')) { // Perú
		$('[data-price="3"]').text("S/."+(price_peru["Puntos de Legado"]));

		$('#zpuMethodPayments').empty();
		$('#zpuMethodPayments').prepend('<img id="zpuMethodPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru1.png" width="25%" height="25%" />');
		$('#zpuMethodPayments').prepend('&nbsp;&nbsp;');
		$('#zpuMethodPayments').prepend('<img id="zpuMethodPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru2.png" width="25%" height="25%" />');
	} else if(region == 1 && !el.hasClass('zpuRegionDisabled1')) { // Argentina
		$('[data-price="3"]').text("$ "+(price_argentina["Puntos de Legado"]));

		$('#zpuMethodPayments').empty();
		$('#zpuMethodPayments').prepend('<img src="https://imgmp.mlstatic.com/org-img/banners/ar/medios/online/575X40.jpg" alt="MercadoPago - Medios de pago" height="40" width="575" />');
		$('#zpuMethodPayments').prepend('<br /><br />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c27.gif" alt="RapiPago" height="25" width="68" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c56.gif" alt="CobroExpress" height="25" width="48" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c63.gif" alt="Ripsa" height="25" width="44" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c36.gif" alt="BaproPagos" height="20" width="68" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c44.gif" alt="ProvinciaPagos" height="21" width="65" />');
		$('#zpuMethodPayments').prepend('<br />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_formo.gif" alt="FormoPagos" height="25" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pagolisto.gif" alt="Pagolisto" height="31" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pampa.gif" alt="PampaPagos" height="30" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_chubut.gif" alt="ChubutPagos" height="30" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_coope.gif" alt="Cooperativa Obrera" height="30" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c68.gif" alt="Pagos Link" height="25" width="26" />');
		$('#zpuMethodPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c71.gif" alt="Banelco" height="25" width="38" />');
	} else if(region == 2 && !el.hasClass('zpuRegionDisabled2')) { // Paypal
		$('[data-price="3"]').text((price_paypal["Puntos de Legado"])+" USD");

		$('#zpuMethodPayments').empty();
		$('#zpuMethodPayments').prepend('<img id="zpuMethodPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_paypal.png" width="25%" height="25%" />');
	} else if(region == 3 && !el.hasClass('zpuRegionDisabled3')) { // Chile
		$('[data-price="3"]').text((price_chile["Puntos de Legado"])+" CLP");

		$('#zpuMethodPayments').empty();
		$('#zpuMethodPayments').prepend('<img id="zpuMethodPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_chile.png" width="25%" height="25%" />');
	}
}

function validateToFinish() {
	var accept_terms = $('#zpuAcceptTerms').hasClass('ipsToggle_on');
	return accept_terms;
}

$(function() {
	$.ajax({
		url: url_tems,
		success:function(data) {
			$('#terms').html(data);
		}
	});

	var el_class = '.zpuMoneyRegionClass a';
	var el = $(el_class);

	if(!el.hasClass('zpuRegionDisabled0')) {
		loadInformation(0);
	} else {
		if(!el.hasClass('zpuRegionDisabled1')) {
			loadInformation(1);
		} else {
			if(!el.hasClass('zpuRegionDisabled2')) {
				loadInformation(2);
			} else {
				loadInformation(3);
			}
		}
	}

	$('[data-region="0"]').text(regions[0]);
	$('[data-region="1"]').text(regions[1]);
	$('[data-region="2"]').text(regions[2]);
	$('[data-region="3"]').text(regions[3]);

	var ps = $("#ps").attr("data-ps");
	var total_peru = (ps * price_peru["Puntos de Legado"]);
	var total_argentina = (ps * price_argentina["Puntos de Legado"]);
	var total_paypal = (ps * price_paypal["Puntos de Legado"]);
	var total_chile = (ps * price_chile["Puntos de Legado"]);

	$('[data-price-total="0"]').text("S/."+total_peru);
	$('[data-price-total="1"]').text("$ "+total_argentina);
	$('[data-price-total="2"]').text(total_paypal+" USD");
	$('[data-price-total="3"]').text(total_chile+" CLP");

	$('body').on('click', el_class, function(e) {
		var el = $(this);

		if(!el.hasClass('ipsButton_disabled')) {
			$('.zpuMoneyRegionClass .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
			el.addClass('ipsButton_primary').removeClass('ipsButton_light');
		}

		loadInformation($('#zpuSelectRegion .ipsGrid .ipsButton').index(this));
	});

	$('body').on('click', '.zpuContinue', function(e) {
		switch(step) {
			case 1: {
				$("#zpuDataConfirm").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				$('#zpuFinishBuy').show();

				if($('[name="zpu_region"]').val() == 0) {
					$('[name="zpu_total"]').val(total_peru);
				} else if($('[name="zpu_region"]').val() == 1) {
					$('[name="zpu_total"]').val(total_argentina);
				} else if($('[name="zpu_region"]').val() == 2) {
					$('[name="zpu_total"]').val(total_paypal);
				} else if($('[name="zpu_region"]').val() == 3) {
					$('[name="zpu_total"]').val(total_chile);
				}

				break;
			}
		}

		$('.cVentas_stepsActive').removeClass('cVentas_stepsActive').addClass('cVentas_stepsDone');
		$('.cVentas_stepsHeader [data-step="'+(++step)+'"]').addClass('cVentas_stepsActive');
		$("html, body").animate({scrollTop:$('#elContent').offset().top},300);
	});

	$('#zpuAcceptTerms').click(function() {
		$(this).toggleClass('ipsToggle_on ipsToggle_off');

		if(validateToFinish()) {
			$('#zpuFinishButton').removeClass('ipsButton_alternate ipsButton_disabled').addClass('ipsButton_primary');
		} else {
			$('#zpuFinishButton').addClass('ipsButton_alternate ipsButton_disabled').removeClass('ipsButton_primary');
		}
	});

	$('#zpuFinishButton').click(function() {
		if(!validateToFinish()) {
			ips.ui.alert.show({
				type:'alert',
				icon:'fa fa-warning',
				message:'Tenés que aceptar los términos y condiciones para continuar.'
			});
		} else {
			$.ajax({
				'dataType': 'html',
				'url': base_url+'/?app=ventas&module=main&controller=main&do=zpuSendData',
				'method': 'post',
				'data': $('#zpuDataVentas').serialize(),
				beforeSend: function() {
					$('#zpuFinishButton').remove();
					$('#zpuFinishBuy .ipsLoading').show();
				},
				success: function(html) {
					$('#zpuFinishBuy .ipsLoading').hide();

					data = html.split("|");

					dialogPagos = ips.ui.dialog.create({
						content: $('#linkPayments'),
						title: 'Compra generada y finalizada',
						size: 'narrow',
						close: false
					});

					$('#linkPayments a.ipsButton_primary').each(function(i) {
						$(this).attr('href', $(this).attr('href') + data[i]);
					});
					
					$('#linkPayments input').each(function(i) {
						$(this).val($(this).val() + data[i]);
					});

					dialogPagos.show();
				},
				error: function(html) {
					ips.ui.alert.show({
						type: 'alert',
						icon: 'fa fa-warning',
						message: html.responseText,
						subText: 'Recargá la página y volvé a intentarlo'
					});
				}
			});
		}
	});
});