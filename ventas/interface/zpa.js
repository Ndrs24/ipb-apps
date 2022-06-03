var step = 1;
var regions = ["Perú", "Argentina", "Paypal", "Chile"];
var price_peru = {"Puntos Humanos":0.20,"Puntos Zombies":0.20,"Puntos de Legado":0.30,"Saldo":0.10,"Diamantes":0.35};
var price_argentina = {"Puntos Humanos":2.00,"Puntos Zombies":2.00,"Puntos de Legado":3.00,"Saldo":1.00,"Diamantes":5.00};
var price_paypal = {"Puntos Humanos":0.05,"Puntos Zombies":0.05,"Puntos de Legado":0.075,"Saldo":0.025,"Diamantes":0.10};
var price_chile = {"Puntos Humanos":0.0,"Puntos Zombies":0.0,"Puntos de Legado":0.0,"Saldo":0.0,"Diamantes":0.0};
var base_url = '';
var url_tems = base_url+'/terms';

function loadInformation(region) {
	var el_class = '.zpaMoneyRegionClass a';
	var el = $(el_class);

	if(region == 0 && !el.hasClass('zpaRegionDisabled0')) { // Perú
		$('[data-price="1"]').text("S/."+(price_peru["Puntos Humanos"]));
		$('[data-price="2"]').text("S/."+(price_peru["Puntos Zombies"]));
		$('[data-price="3"]').text("S/."+(price_peru["Puntos de Legado"]));
		$('[data-price="4"]').text("S/."+(price_peru["Saldo"]));
		$('[data-price="5"]').text("S/."+(price_peru["Diamantes"]));

		$('#zpaMethorPayments').empty();
		$('#zpaMethorPayments').prepend('<img id="zpaMethorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru1.png" width="25%" height="25%" />');
		$('#zpaMethorPayments').prepend('&nbsp;&nbsp;');
		$('#zpaMethorPayments').prepend('<img id="zpaMethorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru2.png" width="25%" height="25%" />');
	} else if(region == 1 && !el.hasClass('zpaRegionDisabled1')) { // Argentina
		$('[data-price="1"]').text("$ "+(price_argentina["Puntos Humanos"]));
		$('[data-price="2"]').text("$ "+(price_argentina["Puntos Zombies"]));
		$('[data-price="3"]').text("$ "+(price_argentina["Puntos de Legado"]));
		$('[data-price="4"]').text("$ "+(price_argentina["Saldo"]));
		$('[data-price="5"]').text("$ "+(price_argentina["Diamantes"]));

		$('#zpaMethorPayments').empty();
		$('#zpaMethorPayments').prepend('<img src="https://imgmp.mlstatic.com/org-img/banners/ar/medios/online/575X40.jpg" alt="MercadoPago - Medios de pago" height="40" width="575" />');
		$('#zpaMethorPayments').prepend('<br /><br />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c27.gif" alt="RapiPago" height="25" width="68" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c56.gif" alt="CobroExpress" height="25" width="48" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c63.gif" alt="Ripsa" height="25" width="44" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c36.gif" alt="BaproPagos" height="20" width="68" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c44.gif" alt="ProvinciaPagos" height="21" width="65" />');
		$('#zpaMethorPayments').prepend('<br />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_formo.gif" alt="FormoPagos" height="25" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pagolisto.gif" alt="Pagolisto" height="31" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pampa.gif" alt="PampaPagos" height="30" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_chubut.gif" alt="ChubutPagos" height="30" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_coope.gif" alt="Cooperativa Obrera" height="30" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c68.gif" alt="Pagos Link" height="25" width="26" />');
		$('#zpaMethorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c71.gif" alt="Banelco" height="25" width="38" />');
	} else if(region == 2 && !el.hasClass('zpaRegionDisabled2')) { // Paypal
		$('[data-price="1"]').text((price_paypal["Puntos Humanos"])+" USD");
		$('[data-price="2"]').text((price_paypal["Puntos Zombies"])+" USD");
		$('[data-price="3"]').text((price_paypal["Puntos de Legado"])+" USD");
		$('[data-price="4"]').text((price_paypal["Saldo"])+" USD");
		$('[data-price="5"]').text((price_paypal["Diamantes"])+" USD");

		$('#zpaMethorPayments').empty();
		$('#zpaMethorPayments').prepend('<img id="zpaMethorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_paypal.png" width="25%" height="25%" />');
	} else if(region == 3 && !el.hasClass('zpaRegionDisabled3')) { // Chile
		$('[data-price="1"]').text((price_chile["Puntos Humanos"])+" CLP");
		$('[data-price="2"]').text((price_chile["Puntos Zombies"])+" CLP");
		$('[data-price="3"]').text((price_chile["Puntos de Legado"])+" CLP");
		$('[data-price="4"]').text((price_chile["Saldo"])+" CLP");
		$('[data-price="5"]').text((price_chile["Diamantes"])+" CLP");

		$('#zpaMethorPayments').empty();
		$('#zpaMethorPayments').prepend('<img id="zpaMethorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_chile.png" width="25%" height="25%" />');
	}
}

function validateToFinish() {
	var accept_terms = $('#zpaAcceptTerms').hasClass('ipsToggle_on');
	return accept_terms;
}

$(function() {
	$.ajax({
		url: url_tems,
		success:function(data) {
			$('#terms').html(data);
		}
	});

	var el_class = '.zpaMoneyRegionClass a';
	var el = $(el_class);

	if(!el.hasClass('zpaRegionDisabled0')) {
		loadInformation(0);
	} else {
		if(!el.hasClass('zpaRegionDisabled1')) {
			loadInformation(1);
		} else {
			if(!el.hasClass('zpaRegionDisabled2')) {
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

	var ph = $("#ph").attr("data-ph");

	if(ph == null) {
		ph = 0;
	}
	
	var pz = $("#pz").attr("data-pz");

	if(pz == null) {
		pz = 0;
	}

	var pl = $("#pl").attr("data-pl");

	if(pl == null) {
		pl = 0;
	}

	var ps = $("#ps").attr("data-ps");

	if(ps == null) {
		ps = 0;
	}

	var pd = $("#pd").attr("data-pd");

	if(pd == null) {
		pd = 0;
	}

	var total_peru = (ph * price_peru["Puntos Humanos"]) + (pz * price_peru["Puntos Zombies"]) + (pl * price_peru["Puntos de Legado"]) + (ps * price_peru["Saldo"]) + (pd * price_peru["Diamantes"]);
	var total_argentina = (ph * price_argentina["Puntos Humanos"]) + (pz * price_argentina["Puntos Zombies"]) + (pl * price_argentina["Puntos de Legado"]) + (ps * price_argentina["Saldo"]) + (pd * price_argentina["Diamantes"]);
	var total_paypal = (ph * price_paypal["Puntos Humanos"]) + (pz * price_paypal["Puntos Zombies"]) + (pl * price_paypal["Puntos de Legado"]) + (ps * price_paypal["Saldo"]) + (pd * price_paypal["Diamantes"]);
	var total_chile = (ph * price_chile["Puntos Humanos"]) + (pz * price_chile["Puntos Zombies"]) + (pl * price_chile["Puntos de Legado"]) + (ps * price_chile["Saldo"]) + (pd * price_chile["Diamantes"]);

	$('[data-price-total="0"]').text("S/."+total_peru);
	$('[data-price-total="1"]').text("$ "+total_argentina);
	$('[data-price-total="2"]').text(total_paypal+" USD");
	$('[data-price-total="3"]').text(total_chile+" CLP");

	$('body').on('click', el_class, function(e) {
		var el = $(this);

		if(!el.hasClass('ipsButton_disabled')) {
			$('.zpaMoneyRegionClass .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
			el.addClass('ipsButton_primary').removeClass('ipsButton_light');
		}

		loadInformation($('#zpaSelectRegion .ipsGrid .ipsButton').index(this));
	});

	$('body').on('click', '.zpaContinue', function(e) {
		switch(step) {
			case 1: {
				$("#zpaDataConfirm").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				$('#zpaFinishBuy').show();

				if($('[name="zpa_region"]').val() == 0) {
					$('[name="zpa_total"]').val(total_peru);
				} else if($('[name="zpa_region"]').val() == 1) {
					$('[name="zpa_total"]').val(total_argentina);
				} else if($('[name="zpa_region"]').val() == 2) {
					$('[name="zpa_total"]').val(total_paypal);
				} else if($('[name="zpa_region"]').val() == 3) {
					$('[name="zpa_total"]').val(total_chile);
				}

				break;
			}
		}

		$('.cVentas_stepsActive').removeClass('cVentas_stepsActive').addClass('cVentas_stepsDone');
		$('.cVentas_stepsHeader [data-step="'+(++step)+'"]').addClass('cVentas_stepsActive');
		$("html, body").animate({scrollTop:$('#elContent').offset().top},300);
	});

	$('#zpaAcceptTerms').click(function() {
		$(this).toggleClass('ipsToggle_on ipsToggle_off');

		if(validateToFinish()) {
			$('#zpaFinishButton').removeClass('ipsButton_alternate ipsButton_disabled').addClass('ipsButton_primary');
		} else {
			$('#zpaFinishButton').addClass('ipsButton_alternate ipsButton_disabled').removeClass('ipsButton_primary');
		}
	});

	$('#zpaFinishButton').click(function() {
		if(!validateToFinish()) {
			ips.ui.alert.show({
				type:'alert',
				icon:'fa fa-warning',
				message:'Tenés que aceptar los términos y condiciones para continuar.'
			});
		} else {
			$.ajax({
				'dataType': 'html',
				'url': base_url+'/?app=ventas&module=main&controller=main&do=zpaSendData',
				'method': 'post',
				'data': $('#zpaDataVentas').serialize(),
				beforeSend: function() {
					$('#zpaFinishButton').remove();
					$('#zpaFinishBuy .ipsLoading').show();
				},
				success: function(html) {
					$('#zpaFinishBuy .ipsLoading').hide();

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