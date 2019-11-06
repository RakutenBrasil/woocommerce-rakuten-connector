(function( $ ) {
    'use strict';

    $(document).ready(function() {

        function loadValidateCredential(apiKey, document, environment) {
            var text = environment === 'sandbox' ? 'Sandbox' : 'Produção';
            var params = {
                action: 'validate_credential', // this is the function in your functions.php that will be triggered
                apiKey: apiKey,
                document: document,
                environment: environment
            };
            $.ajax({
                url: ajax_object.ajaxurl, // this is the object instantiated in wp_localize_script function
                type: 'POST',
                data: params,
                success: function( response ) {
                    if (response == 200) {
                        Swal.fire({
                            title: response + ' OK: Parabéns',
                            html: '<p>Suas credenciais estão corretas.' + '</p>' +
                                '<p>Ambiente: ' + '<strong>' + text + '</strong></p>',
                            type: 'success',
                            confirmButtonText: '<i class="fa fa-thumbs-up"></i> Fechar'
                        });

                    } else {
                        Swal.fire({
                            title: response + ' Erro',
                            html: '<p>Verifique suas credenciais com o atendimento GenPay</p>' +
                                '<p>Ambiente: ' + '<strong>' + text + '</strong></p>',
                            type: 'error',
                            cancelButtonText: '<i class="fa fa-thumbs-down"></i> Fechar'

                        });
                    }

                },
                error: function(response) {
                    console.log(response);
                    Swal.fire({
                        title: response + ' Erro',
                        html: '<p>Ocorreu problema na integração</p>' +
                            '<p>Ambiente: ' + '<strong>' + text + '</strong></p>',
                        type: 'error',
                        cancelButtonText: '<i class="fa fa-thumbs-down"></i> Fechar'

                    });
                    return false;
                }
            });
        }
        $("#woocommerce_rakuten-pay-banking-billet_validate_credential").click(function () {
            loadValidateCredential(
                $("#woocommerce_rakuten-pay-banking-billet_api_key").val(),
                $("#woocommerce_rakuten-pay-banking-billet_document").val(),
                $("#woocommerce_rakuten-pay-banking-billet_environment").val()
            );
        });
        $("#woocommerce_rakuten-pay-credit-card_validate_credential").click(function () {
            loadValidateCredential(
                $("#woocommerce_rakuten-pay-credit-card_api_key").val(),
                $("#woocommerce_rakuten-pay-credit-card_document").val(),
                $("#woocommerce_rakuten-pay-credit-card_environment").val()
            );
        });
    });

}( jQuery ));
