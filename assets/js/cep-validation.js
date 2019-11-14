(function( $ ) {
    'use strict';

    $(document).ready(function() {

        function limpa_formulário_cep() {
            // Limpa valores do formulário de cep.
            $("#billing_address_1").val("");
            $("#billing_address_2").val("");
            $("#billing_neighborhood").val("");
            $("#billing_city").val("");
            $("#billing_state").val("");

        }

        //Quando o campo cep perde o foco.
        $("#billing_postcode").blur(function() {

            //Nova variável "cep" somente com dígitos.
            var cep = $(this).val().replace(/\D/g, '');

            //Verifica se campo cep possui valor informado.
            if (cep != "") {

                //Expressão regular para validar o CEP.
                var validacep = /^[0-9]{8}$/;

                //Valida o formato do CEP.
                if(validacep.test(cep)) {

                    //Preenche os campos com "..." enquanto consulta webservice.
                    $("#billing_address_1").val("...");
                    $("#billing_address_2").val("");
                    $("#billing_neighborhood").val("...");
                    $("#billing_city").val("...");

                    //Consulta o webservice viacep.com.br/
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                        if (!("erro" in dados)) {
                            //Atualiza os campos com os valores da consulta.
                            $("#billing_address_1").val(dados.logradouro);
                            $("#billing_address_2").val("");
                            $("#billing_neighborhood").val(dados.bairro);
                            $("#billing_city").val(dados.localidade);
                            $("#billing_state").val(dados.uf).change();
                            $("#billing_number").focus();

                        } //end if.
                        else {
                            //CEP pesquisado não foi encontrado.
                            limpa_formulário_cep();
                            alert("CEP não encontrado.");
                        }
                    });
                } //end if.
                else {
                    //cep é inválido.
                    limpa_formulário_cep();
                    alert("Formato de CEP inválido.");
                }
            } //end if.
            else {
                //cep sem valor, limpa formulário.
                limpa_formulário_cep();
            }
        });

        //Quando o campo cep perde o foco.
        $("#shipping_postcode").blur(function() {

            //Nova variável "cep" somente com dígitos.
            var cep = $(this).val().replace(/\D/g, '');

            //Verifica se campo cep possui valor informado.
            if (cep != "") {

                //Expressão regular para validar o CEP.
                var validacep = /^[0-9]{8}$/;

                //Valida o formato do CEP.
                if(validacep.test(cep)) {

                    //Preenche os campos com "..." enquanto consulta webservice.
                    $("#shipping_address_1").val("...");
                    $("#shipping_address_2").val("");
                    $("#shipping_neighborhood").val("...");
                    $("#shipping_city").val("...");

                    //Consulta o webservice viacep.com.br/
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                        if (!("erro" in dados)) {
                            //Atualiza os campos com os valores da consulta.
                            $("#shipping_address_1").val(dados.logradouro);
                            $("#shipping_address_2").val("");
                            $("#shipping_neighborhood").val(dados.bairro);
                            $("#shipping_city").val(dados.localidade);
                            $("#shipping_state").val(dados.uf).change();
                            $("#shipping_number").focus();

                        } //end if.
                        else {
                            //CEP pesquisado não foi encontrado.
                            limpa_formulário_cep();
                            alert("CEP não encontrado.");
                        }
                    });
                } //end if.
                else {
                    //cep é inválido.
                    limpa_formulário_cep();
                    alert("Formato de CEP inválido.");
                }
            } //end if.
            else {
                //cep sem valor, limpa formulário.
                limpa_formulário_cep();
            }
        });
    });

}( jQuery ));