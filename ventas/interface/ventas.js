var step = 1;
var regions = ["Perú", "Argentina", "Paypal", "Chile"];
var servers = ["Todos", "Zombie Escape", "ZP Umbrella", "Only Dust2 & Inferno", "Deathrun", "Jailbreak", "CS:GO Arena 1v1", "Atrapa al Traidor (TTT)", "ZP Annihilation", "Surf + Kills", "CS:GO Público", "Tower Defense", "HNS + Laser", "Mapas Frutas", "KZ + Bhop", "Pug - Automix 5v5", "Gungame", "Pug - Scrim 5v5", "Deathmatch + FFA"];
var price_peru = {"VIP":{"7":5,"15":10,"30":20,"60":40,"90":60},"VIP Full":{"7":10,"15":15,"30":25,"60":50,"90":75},"Admin CS":{"30":30,"60":60,"90":90},"Admin CS Full":{"30":40,"60":80,"90":120}};
var price_argentina = {"VIP":{"7":75,"15":120,"30":200,"60":375,"90":500},"VIP Full":{"7":100,"15":150,"30":225,"60":400,"90":550},"Admin CS":{"30":225,"60":450,"90":675},"Admin CS Full":{"30":275,"60":500,"90":750}};
var price_paypal = {"VIP":{"7":1,"15":2,"30":5,"60":10,"90":15},"VIP Full":{"7":2,"15":5,"30":10,"60":20,"90":30},"Admin CS":{"30":10,"60":20,"90":30},"Admin CS Full":{"30":15,"60":30,"90":45}};
var price_chile = {"VIP":{"7":0,"15":0,"30":0,"60":0,"90":0},"VIP Full":{"7":0,"15":0,"30":0,"60":0,"90":0},"Admin CS":{"30":0,"60":0,"90":0},"Admin CS Full":{"30":0,"60":0,"90":0}};
var base_url = '';
var url_tems = base_url+'/terms';

function verifyDataInputs(tagcs, password) {
	if(tagcs.length == 0 || password.length == 0) {
		ips.ui.alert.show({
			type: 'alert',
			icon: 'fa fa-warning',
			message: 'Debes completar todos los campos.'
		});

		return false;
	} else if(password.length < 6 || password.length > 16 || !password.match(/^[a-zA-Z0-9]+$/)) {
		ips.ui.alert.show({
			type: 'alert',
			icon: 'fa fa-warning',
			message: 'Tu contraseña debe tener de 6 a 16 caracteres que deben ser solamente números y letras.'
		});

		return false;
	} else if(tagcs.length < 4 || tagcs.length > 30) {
		ips.ui.alert.show({
			type: 'alert',
			icon: 'fa fa-warning',
			message: 'Tu Tag CS debe tener de 4 a 30 caracteres.'
		});

		return false;
	} else {
		return true;
	}
}

function validateToFinish() {
	var benefit = $('[name="benefit"]').val();
	var is_admin = (benefit == "Admin CS" || benefit == "Admin CS Full");
	var accept_terms = $('#acceptTerms').hasClass('ipsToggle_on');
	var accept_to_admin = $('#acceptToAdmin').hasClass('ipsToggle_on');

	return accept_terms && ((is_admin && accept_to_admin) || !is_admin);
}

$(function() {
	$.ajax({
		url: url_tems,
		success:function(data) {
			$('#terms').html(data);
		}
	});

	$('body').on('click', '#priceInfoDialog', function(e) {
		$('[data-per-price="1"]').text("S/."+price_peru["VIP"]["30"]+" /mes");
		$('[data-per-price="2"]').text("S/."+price_peru["VIP Full"]["30"]+" /mes");
		$('[data-per-price="3"]').text("S/."+price_peru["Admin CS"]["30"]+" /mes");
		$('[data-per-price="4"]').text("S/."+price_peru["Admin CS Full"]["30"]+" /mes");

		$('[data-arg-price="1"]').text("$ "+price_argentina["VIP"]["30"]+" /mes");
		$('[data-arg-price="2"]').text("$ "+price_argentina["VIP Full"]["30"]+" /mes");
		$('[data-arg-price="3"]').text("$ "+price_argentina["Admin CS"]["30"]+" /mes");
		$('[data-arg-price="4"]').text("$ "+price_argentina["Admin CS Full"]["30"]+" /mes");

		$('[data-pp-price="1"]').text(price_paypal["VIP"]["30"]+" USD /mes");
		$('[data-pp-price="2"]').text(price_paypal["VIP Full"]["30"]+" USD /mes");
		$('[data-pp-price="3"]').text(price_paypal["Admin CS"]["30"]+" USD /mes");
		$('[data-pp-price="4"]').text(price_paypal["Admin CS Full"]["30"]+" USD /mes");

		$('[data-cl-price="1"]').text(price_chile["VIP"]["30"]+" CLP /mes");
		$('[data-cl-price="2"]').text(price_chile["VIP Full"]["30"]+" CLP /mes");
		$('[data-cl-price="3"]').text(price_chile["Admin CS"]["30"]+" CLP /mes");
		$('[data-cl-price="4"]').text(price_chile["Admin CS Full"]["30"]+" CLP /mes");
	});

	$('body').on('click', '.continue', function(e) {
		switch(step) {
			case 1: {
				$("#dataSelectBenefits").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				$.ajax({
					url: base_url+'/?app=ventas&module=main&controller=main&do='+(($('#dataSelectBenefits .continue').index(this)) ? "admin" : "vip")+'Data',
					success: function(data) {
						$('#verifyData').removeClass('ipsHide').html(data);
					}
				})

				break;
			} case 2: {
				var tagcs = $('[name="tagcs"]').val();
				var setinfo = $('[name="setinfo"]').val();

				if(!verifyDataInputs(tagcs, setinfo)) {
					return false;
				} else {
					$("#stepTwo").animate({height:0, opacity:0}, 'slow', function() {
						$(this).hide();
					});

					$('#stepThree').show();
				}

				break;
			} case 3: {
				$("#stepThree").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				if($('[name="region"]').val() == 0) { // Perú
					$('[data-price="VIP"]').text("S/."+price_peru["VIP"]["30"]);
					$('[data-price="VIP Full"]').text("S/."+price_peru["VIP Full"]["30"]);

					$('[data-price="Admin CS"]').text("S/."+price_peru["Admin CS"]["30"]);
					$('[data-price="Admin CS Full"]').text("S/."+price_peru["Admin CS Full"]["30"]);
				} else if($('[name="region"]').val() == 1) { // Argentina
					$('[data-price="VIP"]').text("$ "+price_argentina["VIP"]["30"]);
					$('[data-price="VIP Full"]').text("$ "+price_argentina["VIP Full"]["30"]);

					$('[data-price="Admin CS"]').text("$ "+price_argentina["Admin CS"]["30"]);
					$('[data-price="Admin CS Full"]').text("$ "+price_argentina["Admin CS Full"]["30"]);
				} else if($('[name="region"]').val() == 2) { // Paypal
					$('[data-price="VIP"]').text(price_paypal["VIP"]["30"]+" USD");
					$('[data-price="VIP Full"]').text(price_paypal["VIP Full"]["30"]+" USD");

					$('[data-price="Admin CS"]').text(price_paypal["Admin CS"]["30"]+" USD");
					$('[data-price="Admin CS Full"]').text(price_paypal["Admin CS Full"]["30"]+" USD");
				} else if($('[name="region"]').val() == 3) { // Chile
					$('[data-price="VIP"]').text(price_chile["VIP"]["30"]+" CLP");
					$('[data-price="VIP Full"]').text(price_chile["VIP Full"]["30"]+" CLP");

					$('[data-price="Admin CS"]').text(price_chile["Admin CS"]["30"]+" CLP");
					$('[data-price="Admin CS Full"]').text(price_chile["Admin CS Full"]["30"]+" CLP");
				}

				$('#stepFour').show();

				break;
			} case 4: {
				if(!$('[name="server"]').val()) {
					ips.ui.alert.show({
						type: 'alert',
						icon: 'fa fa-warning',
						message: 'Debes completar los detalles para seguir tu compra.'
					});

					return false;
				}

				$("#verifyData").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				$('[data-confirm="tagcs"]').text($('[name="tagcs"]').val());
				$('[data-confirm="benefit"]').text($('[name="benefit"]').val());
				$('[data-confirm="region"]').text(regions[$('[name="region"]').val()]);
				$('[data-confirm="server"]').text(servers[$('[name="server"]').val()]);
				$('[data-confirm="days"]').text($('[name="days"]').val()+" días");

				if($('[name="region"]').val() == 0) { // Perú
					$('[data-confirm="price"]').text("S/."+(price_peru[$('[name="benefit"]').val()][$('[name="days"]').val()]));
					$('[name="money_real"]').val(price_peru[$('[name="benefit"]').val()][$('[name="days"]').val()]);
				} else if($('[name="region"]').val() == 1) { // Argentina
					$('[data-confirm="price"]').text("$ "+(price_argentina[$('[name="benefit"]').val()][$('[name="days"]').val()]));
					$('[name="money_real"]').val(price_argentina[$('[name="benefit"]').val()][$('[name="days"]').val()]);
				} else if($('[name="region"]').val() == 2) { // Paypal
					$('[data-confirm="price"]').text(""+(price_paypal[$('[name="benefit"]').val()][$('[name="days"]').val()])+" USD");
					$('[name="money_real"]').val(price_paypal[$('[name="benefit"]').val()][$('[name="days"]').val()]);
				} else if($('[name="region"]').val() == 3) { // Chile
					$('[data-confirm="price"]').text(""+(price_chile[$('[name="benefit"]').val()][$('[name="days"]').val()])+" CLP");
					$('[name="money_real"]').val(price_chile[$('[name="benefit"]').val()][$('[name="days"]').val()]);
				}

				$('#dataConfirm').show();

				break;
			} case 5: {
				$("#dataConfirm").animate({height:0, opacity:0}, 'slow', function() {
					$(this).hide();
				});

				$('#finishBuy').show();

				if($('[name="benefit"]').val() == "Admin CS" || $('[name="benefit"]').val() == "Admin CS Full") {
					$('#acceptToAdmin').parent().show();
				} else {
					$('#acceptToAdmin').parent().hide();
				}

				if($('[name="region"]').val() == 0) { // Perú
					$('#methorPayments').prepend('<img id="methorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru1.png" width="25%" height="25%" />');
					$('#methorPayments').prepend('&nbsp;&nbsp;');
					$('#methorPayments').prepend('<img id="methorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_peru2.png" width="25%" height="25%" />');
				} else if($('[name="region"]').val() == 1) { // Argentina
					$('#methorPayments').prepend('<img src="https://imgmp.mlstatic.com/org-img/banners/ar/medios/online/575X40.jpg" alt="MercadoPago - Medios de pago" height="40" width="575" />');
					$('#methorPayments').prepend('<br /><br />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c20.gif" alt="PagoFacil" height="25" width="30" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c27.gif" alt="RapiPago" height="25" width="68" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c56.gif" alt="CobroExpress" height="25" width="48" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c63.gif" alt="Ripsa" height="25" width="44" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c36.gif" alt="BaproPagos" height="20" width="68" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r2_c44.gif" alt="ProvinciaPagos" height="21" width="65" />');
					$('#methorPayments').prepend('<br />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_formo.gif" alt="FormoPagos" height="25" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pagolisto.gif" alt="Pagolisto" height="31" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_pampa.gif" alt="PampaPagos" height="30" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_chubut.gif" alt="ChubutPagos" height="30" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_coope.gif" alt="Cooperativa Obrera" height="30" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c68.gif" alt="Pagos Link" height="25" width="26" />');
					$('#methorPayments').prepend('<img src="https://www.cuentadigital.com/img/logos/logo_r1_c71.gif" alt="Banelco" height="25" width="38" />');
				} else if($('[name="region"]').val() == 2) { // Paypal
					$('#methorPayments').prepend('<img id="methorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_paypal.png" width="25%" height="25%" />');
				} else if($('[name="region"]').val() == 3) { // Chile
					$('#methorPayments').prepend('<img id="methorPayments_img" src="https://www.DrunkGaming.net/esthetic/ventas_chile.png" width="25%" height="25%" />');
				}

				break;
			}
		}

		$('.cVentas_stepsActive').removeClass('cVentas_stepsActive').addClass('cVentas_stepsDone');
		$('.cVentas_stepsHeader [data-step="'+(++step)+'"]').addClass('cVentas_stepsActive');
		$("html, body").animate({scrollTop:$('#elContent').offset().top},300);
	});

	$('body').on('click', '.back', function(e) {
		switch(step) {
			case 2: {
				$('#verifyData').html('').addClass('ipsHide');
				$('#dataSelectBenefits').removeAttr('style');

				break;
			} case 3: {
				$('#stepTwo').removeAttr('style');
				$('#stepThree').addClass('ipsHide').removeAttr('style');

				break;
			} case 4: {
				$('#stepThree').removeAttr('style').show();
				$('#stepFour').addClass('ipsHide').removeAttr('style');

				break;
			} case 5: {
				$('#verifyData').show().removeAttr('style');
				$('#dataConfirm').hide();
				$('#stepThree').removeClass('ipsHide');
				$('#stepFour').removeClass('ipsHide');

				break;
			}
		}

		$('.cVentas_stepsActive').removeClass('cVentas_stepsActive');
		$('.cVentas_stepsHeader [data-step="'+(--step)+'"]').removeClass('cVentas_stepsDone').addClass('cVentas_stepsActive');
		$("html, body").animate({scrollTop:$('#elContent').offset().top},300);
	});

	$('body').on('click', '#checkTagExists', function(e) {
		var name = $('[name="tagcs"]').val();

		if(name.length < 4 || name.length > 30) {
			ips.ui.alert.show({
				type:'alert',
				icon:'fa fa-warning',
				message:'Completa el campo... Tu Tag CS debe tener de 4 a 30 caracteres.'
			});

			return false;
		}

		$.ajax({
			url: base_url+'/?app=adminpanel&module=main&controller=panel&do=tagExists&input='+name,
			success: function(data) {
				ips.ui.alert.show({
					type: 'alert',
					icon: 'fa fa-warning',
					message: data
				});

				return false;
			}
		});
	});

	$('body').on('click', '.moneyRegionClass a', function(e) {
		var el = $(this);

		if(!el.hasClass('ipsButton_disabled')) {
			$('.moneyRegionClass .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
			el.addClass('ipsButton_primary').removeClass('ipsButton_light');
		}
		
		$('[name="region"]').val($('#stepThree .ipsGrid .ipsButton').index(this));
	});

	$('body').on('click', '.selectServerVip a', function(e) {
		var el = $(this);
		var server = $('#stepFour .ipsGrid .ipsButton').index(this);

		$('.selectServerVip .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
		el.addClass('ipsButton_primary').removeClass('ipsButton_light');
		$('[name="server"]').val(server);

		if(server == 0) {
			$('[name="benefit"]').val("VIP Full");
		} else {
			$('[name="benefit"]').val("VIP");
		}
	});

	$('body').on('click', '.selectServerAdmin a', function(e) {
		var el = $(this);
		var server = $('#stepFour .ipsGrid .ipsButton').index(this);

		$('.selectServerAdmin .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
		el.addClass('ipsButton_primary').removeClass('ipsButton_light');
		$('[name="server"]').val(server);

		if(server == 0) {
			$('[name="benefit"]').val("Admin CS Full");
		} else {
			$('[name="benefit"]').val("Admin CS");
		}
	});

	$('body').on('click', '.daysBenefitsClass a', function(e) {
		var el = $(this);

		$('.daysBenefitsClass .ipsButton_primary').removeClass('ipsButton_primary').addClass('ipsButton_light');
		el.addClass('ipsButton_primary').removeClass('ipsButton_light');

		$('[name="days"]').val(el.attr('data-days'));
	});

	$('#acceptTerms, #acceptToAdmin').click(function() {
		$(this).toggleClass('ipsToggle_on ipsToggle_off');

		if(validateToFinish()) {
			$('#finishButton').removeClass('ipsButton_alternate ipsButton_disabled').addClass('ipsButton_primary');
		} else {
			$('#finishButton').addClass('ipsButton_alternate ipsButton_disabled').removeClass('ipsButton_primary');
		}
	});

	$('#finishButton').click(function() {
		if(!validateToFinish()) {
			ips.ui.alert.show({
				type:'alert',
				icon:'fa fa-warning',
				message:'Tenés que aceptar los términos y condiciones para continuar.'
			});
		} else {
			$.ajax({
				'dataType': 'html',
				'url': base_url+'/?app=ventas&module=main&controller=main&do=sendData',
				'method': 'post',
				'data': $('#dataVentas').serialize(),
				beforeSend: function() {
					$('#finishButton').remove();
					$('#finishBuy .ipsLoading').show();
				},
				success: function(html) {
					$('#finishBuy .ipsLoading').hide();

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