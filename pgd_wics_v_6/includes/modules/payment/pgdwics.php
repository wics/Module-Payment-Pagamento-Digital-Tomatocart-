<?php
/***METODO DE PAGAMENTO DIGITAL*************

 *  Wics Tecnologia versão V.5 08/10/2011  *

 *  Modulo de integracao TomatoCarto 1.1.X *

 *  Pagamento Digital                      *

 *  www.wics.com.br       

 *  && isset

 *  ucfirst

 *  urlencode(50090

 *  urldecode

 *  utf8_encode

 *  osc_output_string_protected(

 *  Duvidas wics@wics.com.br             * 

 *  GNU General Public License is a free,  *

 *  copyleft license for software and      *

 *  other kinds of works.  

 * 189.202.477-22

 * 019.868.749-40
 * Para Testes e melhorias
 *  Login: testepd@hotmail.com             *

 *  Senha: contateste 
 
 Deus e maior acima de tudo...

 *******************************************/


  class osC_Payment_pgdwics extends osC_Payment {
    var $_title,

        $_code = 'pgdwics',

        $_author_name = 'Wics Tecnologia',

        $_status = false,

		$enderecoPost,

		$chaveAcesso,

		$urlRetorno,

        $_sort_order;



    function osC_Payment_pgdwics() {

      global $osC_Database, $osC_Language, $osC_ShoppingCart;


	  
      $this->_title = $osC_Language->get('payment_pgdwics_title');

      $this->_method_title = $osC_Language->get('payment_pgdwics_method_title');

      $this->_description = $osC_Language->get('payment_pgdwics_description');

      $this->_status = (defined('MODULE_PAYMENT_PGDWICS_STATUS') && (MODULE_PAYMENT_PGDWICS_STATUS == '1') ? true : false);

      $this->_sort_order = (defined('MODULE_PAYMENT_PGDWICS_SORT_ORDER') ? MODULE_PAYMENT_PGDWICS_SORT_ORDER : null);

	  $this->_chaveAcesso  = MODULE_PAYMENT_PGDWICS_CHAVE;	  

      $this->form_action_url = 'https://www.pagamentodigital.com.br/checkout/pay/';

      $this->apc_url = 'https://www.pagamentodigital.com.br/checkout/pay/';

      if ($this->_status === true) {

      $this->order_status = MODULE_PAYMENT_PGDWICS_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PGDWICS_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;

        

        if ((int)MODULE_PAYMENT_PGDWICS_ZONE > 0) {

          $check_flag = false;

          $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');

          $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);

          $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_PGDWICS_ZONE);

          $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));

          $Qcheck->execute();

          while ($Qcheck->next()) {

            if ($Qcheck->valueInt('zone_id') < 1) {

              $check_flag = true;

              break;

            } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {

              $check_flag = true;

              break;

            }

          }

          if ($check_flag == false) {

            $this->_status = false;

          }

        }

      }

    }



    function selection() {

      return array('id' => $this->_code,

                   'module' => $this->_description);

    }
   
   function confirmation() {

      $this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);

	  osC_Order::process($this->_order_id, $this->order_status);

    }


    function process_button() {

      global $osC_ShoppingCart, $osC_Currencies, $osC_Customer, $osC_Tax, $osC_Database;

     $process_button_string = '';

      if (MODULE_PAYMENT_PGDWICS_GATEWAY_MODE == 'Producao') {

        $params = array('cod_loja' => MODULE_PAYMENT_PGDWICS_ID,

		'success_url' => osc_href_link(FILENAME_CHECKOUT, 'process', 'SSL'), 

        'cancel_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'), 

        'declined_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'));      

      }else if (MODULE_PAYMENT_PGDWICS_GATEWAY_MODE == 'Teste') {

        $params = array('cod_loja' => '658926',

		'success_url' => osc_href_link(FILENAME_CHECKOUT, 'process', 'SSL'), 

        'cancel_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'), 

        'declined_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'));

      }
	  
      /*--------------------------------------PARAMENTROS DO PEDIDO--------------------------------------------------*/
      $id_pedido = $this->_order_id; 
      $params['id_pedido'] = $this->_order_id;
      $shipping_tax = ($osC_ShoppingCart->getShippingMethod('cost')) * ($osC_Tax->getTaxRate($osC_ShoppingCart->getShippingMethod('tax_class_id'), $osC_ShoppingCart->getTaxingAddress('country_id'), $osC_ShoppingCart->getTaxingAddress('zone_id')) / 100);

      if (DISPLAY_PRICE_WITH_TAX == '1') {
        $shipping = $osC_ShoppingCart->getShippingMethod('cost');
      } else {
        $shipping = $osC_ShoppingCart->getShippingMethod('cost') + $shipping_tax;
      }
      $params['frete'] = $osC_Currencies->formatRaw($shipping);

      $total_tax = $osC_ShoppingCart->getTax() - $shipping_tax;
      $params['acrescimo'] = $osC_Currencies->formatRaw($total_tax);// Taxa adicional incluida no pedido caso tiver
      $params['valor'] = $osC_Currencies->formatRaw($osC_ShoppingCart->getTotal() - $shipping - $total_tax);//total do pedido

	  
	  /*--------------------DADOS DO POST DE RETORNO AUTOMATICO E ALTERAÇÃO NO STATUS DO PEDIDO -----------------------------*/ 	 	  
   	  $params['tipo_integracao'] = 'PAD';
	  $params['redirect'] = 'true';
      $params['redirect_time'] = '15';
	  $params['url_aviso'] = osc_href_link(FILENAME_CHECKOUT, 'callback&module=pgdwics&id_pedido=' . $this->_order_id, 'SSL', false, false, true);
	  $params['url_retorno'] = osc_href_link(FILENAME_CHECKOUT, 'callback&module=pgdwics&id_pedido=' . $this->_order_id, 'SSL', false, false, true);
	  //$params['url_retorno'] = osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL', false, false, true);
	  
      /*---------------------------------------PARAMENTROS DO CLIENTE---------------------------------------------------*/    

      $params['nome'] = $osC_ShoppingCart->getBillingAddress('firstname') . ' ' . $osC_ShoppingCart->getBillingAddress('lastname');

      $params['endereco'] = $osC_ShoppingCart->getBillingAddress('street_address');

      $params['cidade'] = $osC_ShoppingCart->getBillingAddress('city');

	  $params['bairro'] = $osC_ShoppingCart->getBillingAddress('suburb');

	  $params['estado'] = $osC_ShoppingCart->getBillingAddress('zone_code');

	  /*------------------------------------------- TRATAMENTO CEP --------------------------------------------------------*/

	  $replacements_cep = array(" ", ".", ",", "-", ";");

	  $postcode = str_replace($replacements_cep, "", $postcode);  

	  $params['cep'] = $osC_ShoppingCart->getBillingAddress('postcode');

	  /*--------------------- ---------------------TRATAMENTO TEL -----------------------------------------------------------*/

	  $replacements_tel = array("*","/",")","("," ","'","\"",".", ",", "-", ";");

	  $telephone_number = str_replace($replacements_tel, "", $telephone_number);

      $params['telefone'] = $osC_ShoppingCart->getShippingAddress('telephone_number');

	  /*---------------------------------- TRATAMENTO CPF USANDO O CAMPO FAX -------------------------------------------------*/

	  $replacements_cpf = array("*","/",")","("," ","'","\"",".", ",", "-", ";");

	  $company = str_replace($replacements_cpf, "", $company);
	  
	  $params['cpf'] = $osC_ShoppingCart->getBillingAddress('company');

      $params['email'] = $osC_Customer->getEmailAddress('email_address');

      $params['sexo'] = $osC_Customer->getGender();

	 // $params['complemento'] = $osC_Customer->osc_get_ip_address('customers_ip_address');

      $params['complemento'] = $osC_ShoppingCart->getBillingAddress('shipping_comments');

	  $params['free'] = $osC_ShoppingCart->getBillingAddress('payment_comments');
    

      if ($osC_ShoppingCart->hasShippingAddress()) {

        $params['nome'] = $osC_ShoppingCart->getShippingAddress('firstname') . ' ' . $osC_ShoppingCart->getShippingAddress('lastname');

        $params['endereco'] = $osC_ShoppingCart->getShippingAddress('street_address');

        $params['cep'] = $osC_ShoppingCart->getShippingAddress('postcode');	

      }else {

        $params['nome'] = $params['billing_fullname'];

        $params['endereco'] = $params['billing_address'];

        $params['cep'] = $params['billing_postcode'];

      }
	  
     /*---------------------------------PEGA OS PRODUTOS E QUANTIDADE---------------------------------*/


        $products = array();

        if ($osC_ShoppingCart->hasContents()) {

          $i = 1;

          $products = $osC_ShoppingCart->getProducts();

          foreach($products as $product) {

            $product_name = $product['name'];



            //gift certificate

            if ($product['type'] == PRODUCT_TYPE_GIFT_CERTIFICATE) {

              $product_name .= "\n" . ' - ' . $osC_Language->get('senders_name') . ': ' . $product['gc_data']['senders_name'];



              if ($product['gc_data']['type'] == GIFT_CERTIFICATE_TYPE_EMAIL) {

                $product_name .= "\n" . ' - ' . $osC_Language->get('senders_email')  . ': ' . $product['gc_data']['senders_email'];

              }

              $product_name .= "\n" . ' - ' . $osC_Language->get('recipients_name') . ': ' . $product['gc_data']['recipients_name'];


              if ($product['gc_data']['type'] == GIFT_CERTIFICATE_TYPE_EMAIL) {

                $product_name .= "\n" . ' - ' . $osC_Language->get('recipients_email')  . ': ' . $product['gc_data']['recipients_email'];

              }

              $product_name .= "\n" . ' - ' . $osC_Language->get('message')  . ': ' . $product['gc_data']['message'];

            }


            if ($osC_ShoppingCart->hasVariants($product['id'])) {

              foreach ($osC_ShoppingCart->getVariants($product['id']) as $variant) {

                $product_name .= ' - ' . $variant['groups_name'] . ': ' . $variant['values_name'];

              }

            }

            $product_data = array('produto_descricao_' . $i => $product_name, 'produto_codigo_' . $i => $product['sku'], 'produto_peso_' . $i  => (int) number_format($product['weight'], 2, '', ''), 'produto_qtde_' . $i  => $product['quantity'], 'produto_valor_' . $i  => $product['price']);
            $params = array_merge($params,$product_data);

            $i++;

          }

        }
		
      foreach($params as $key => $value) {

        $process_button_string .= osc_draw_hidden_field($key, $value);

      }

      return $process_button_string;

    }  

/***************RETORNO DO PEDIDO ALTERA O PEDIDO NO BANCO E INSERI COMENTARIOS DE STATUS E ENVIA EMAIL*************/	
	
   function callback() {

      global $osC_Database, $osC_Currencies, $osC_ShoppingCart;

      foreach ($_POST as $key => $value) {

        $post_string .= $key . '=' . urlencode($value) . '&';

      } 
  
    $post_string = substr($post_string, 0, -0);

    $this->_transaction_response = $this->sendTransactionToGateway($this->apc_url, $post_string);
	   
	$token  = MODULE_PAYMENT_PGDWICS_CHAVE;
         $id_transacao = $_POST['id_transacao'];
         $data_transacao = $_POST['data_transacao'];
         $data_credito = $_POST['data_credito'];
         $valor_original = $_POST['valor_original'];
         $valor_loja = $_POST['valor_loja'];
         $valor_total = $_POST['valor_total'];
         $desconto = $_POST['desconto'];
         $acrescimo = $_POST['acrescimo'];
         $tipo_pagamento = $_POST['tipo_pagamento'];
         $parcelas = $_POST['parcelas'];
         $cliente_nome = $_POST['cliente_nome'];
         $cliente_email = $_POST['cliente_email'];
         $cliente_rg = $_POST['cliente_rg'];
         $cliente_data_emissao_rg = $_POST['cliente_data_emissao_rg'];
         $cliente_orgao_emissor_rg = $_POST['cliente_orgao_emissor_rg'];
         $cliente_estado_emissor_rg = $_POST['cliente_estado_emissor_rg'];
         $cliente_cpf = $_POST['cliente_cpf'];
         $cliente_sexo = $_POST['cliente_sexo'];
         $cliente_data_nascimento = $_POST['cliente_data_nascimento'];
         $cliente_endereco = $_POST['cliente_endereco'];
         $cliente_complemento = $_POST['cliente_complemento'];
         $status = $_POST['status'];
         $cod_status = $_POST['cod_status'];
         $cliente_bairro = $_POST['cliente_bairro'];
         $cliente_cidade = $_POST['cliente_cidade'];
         $cliente_estado = $_POST['cliente_estado'];
         $cliente_cep = $_POST['cliente_cep'];
         $frete = $_POST['frete'];
         $tipo_frete = $_POST['tipo_frete'];
         $informacoes_loja = $_POST['informacoes_loja'];
         $id_pedido = $_POST['id_pedido'];
         $free = $_POST['free'];
	/* Essa variável indica a quantidade de produtos retornados */
     $qtde_produtos = $_POST['qtde_produtos'];
     $post = "transacao=$id_transacao" .
     "&status=$status" .
     "&cod_status=$cod_status" .
     "&valor_original=$valor_original" .
     "&valor_loja=$valor_loja" .
     "&token=$token";
	$enderecoPost = 'https://www.pagamentodigital.com.br/checkout/verify/';

    $this->_transaction_response = 'VERIFICADO';
	
       /****************************STATUS DA TRANSAÇÃO****************************************/

      switch ($_POST['cod_status']) {

        case '1'://default 1

          $transaction_type = '5'; //pago

          break;

        case '2'://default 2

          $transaction_type = '8';//cancelado

          break;

        case '0': //default 0

        default:

          $transaction_type = '2';//processando

          break; 
 }
 
       /*******DADOS OBITIDOS DO RETORNO******/
       $comments_retorno = 'Pagamento Digital | Pedido Valor total: R$' . sprintf("%01.2f", $_POST["valor_original"]) . "\n" . "\n" . ' Recebido em  ' . $_POST['data_transacao']  . "\n". "\n" . ' Transacao ID:' . $_POST['id_transacao']  . "\n". "\n" . ' Situação '. utf8_encode($_POST['status']) . '.';

	   $comments_venda = ' Pedido faturado para  : ' . "\n"."\n" . ' Cliente :  ' .  utf8_encode($_POST['cliente_nome']) . "\n". "\n" .' CPF : ' . $_POST['cliente_cpf'] . "\n". "\n" .' Email : ' . utf8_encode($_POST['cliente_email']) . "\n". "\n" .'   Forma de pagamento : ' . utf8_encode($_POST['tipo_pagamento']) . "\n". "\n" . '   Número de Parcelas : ' . $_POST['parcelas'] . "\n". "\n" . ' Situação :  ' . utf8_encode($_POST['status']).  '  .';

       /**************GRAVA OS DADOS NO PEDIDO APOS O RETORNO*************/
	   osC_Order::process($_POST['id_pedido'], $transaction_type, $comments_retorno);   /* Altera o status do pedido, faz os comentarios e envia um novo email*/

	   osC_Order::insertOrderStatusHistory($_POST['id_pedido'], $transaction_type, $comments_venda);  /*Altera o status do pedido, faz os comentarios dos dados do cliente não envia email para o cliente(Reservado)*/
	   
	   $osC_ShoppingCart->reset(true);/********LIMPA O CARRINHO DO CLIENTE******/
	   
	   osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL'));// Redireciona para pagina de finalização do pedido*/

      }  

    }

?>